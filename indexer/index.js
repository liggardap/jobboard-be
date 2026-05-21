const Redis = require('ioredis');
const { Client } = require('@elastic/elasticsearch');

const redis = new Redis({
  host: process.env.REDIS_HOST || 'redis',
  port: parseInt(process.env.REDIS_PORT || '6379'),
});

const es = new Client({
  node: process.env.ELASTICSEARCH_HOST || 'http://elasticsearch:9200',
});

async function handleIndex(payload) {
  const job = JSON.parse(payload);
  await es.index({
    index: 'jobs',
    id: String(job.id),
    document: job,
  });
  console.log(`Indexed job ${job.id}`);
}

async function handleDelete(payload) {
  const { id } = JSON.parse(payload);
  await es.delete({ index: 'jobs', id: String(id) });
  console.log(`Deleted job ${id}`);
}

async function start() {
  const subscriber = redis.duplicate();

  await subscriber.subscribe('jobs:index', 'jobs:delete');
  console.log('Subscribed to jobs:index and jobs:delete');

  subscriber.on('message', async (channel, message) => {
    try {
      if (channel === 'jobs:index') {
        await handleIndex(message);
      } else if (channel === 'jobs:delete') {
        await handleDelete(message);
      }
    } catch (err) {
      console.error(`Error processing message on ${channel}:`, err);
    }
  });
}

start().catch((err) => {
  console.error('Failed to start indexer:', err);
  process.exit(1);
});
