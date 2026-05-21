import { createClient } from 'redis';
import { createEsClient, ensureIndex } from './elasticsearch.js';

const REDIS_URL = process.env.REDIS_URL || 'redis://localhost:6379';
const ES_URL = process.env.ES_URL || 'http://localhost:9200';
const INDEX_NAME = 'jobs';

const redis = createClient({ url: REDIS_URL });
const es = createEsClient(ES_URL);

redis.on('error', (err) => console.error('Redis client error:', err));

async function handleIndex(message) {
  const job = JSON.parse(message);
  await es.index({ index: INDEX_NAME, id: String(job.id), document: job });
  console.log(`Indexed job ${job.id}: ${job.title}`);
}

async function handleDelete(message) {
  const { id } = JSON.parse(message);
  await es.delete({ index: INDEX_NAME, id: String(id) });
  console.log(`Deleted job ${id} from index`);
}

async function start() {
  await redis.connect();
  await es.ping();
  await ensureIndex(es, INDEX_NAME);

  console.log('Indexer ready — subscribed to jobs:index and jobs:delete');

  const subscriber = redis.duplicate();
  await subscriber.connect();

  await subscriber.subscribe('jobs:index', async (message) => {
    try {
      await handleIndex(message);
    } catch (err) {
      console.error('Failed to index job:', err.message);
    }
  });

  await subscriber.subscribe('jobs:delete', async (message) => {
    try {
      await handleDelete(message);
    } catch (err) {
      console.error('Failed to delete job:', err.message);
    }
  });

  const shutdown = async (signal) => {
    console.log(`${signal} received — shutting down gracefully`);
    try {
      await subscriber.unsubscribe();
      await subscriber.quit();
      await redis.quit();
    } catch (err) {
      console.error('Error during shutdown:', err.message);
    }
    process.exit(0);
  };

  process.on('SIGINT', () => shutdown('SIGINT'));
  process.on('SIGTERM', () => shutdown('SIGTERM'));
}

start().catch((err) => {
  console.error('Failed to start indexer:', err);
  process.exit(1);
});
