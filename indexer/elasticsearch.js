import { Client } from '@elastic/elasticsearch';

export const createEsClient = (url) => {
  return new Client({ node: url });
};

export const ensureIndex = async (client, indexName) => {
  const exists = await client.indices.exists({ index: indexName });

  if (exists) return;

  await client.indices.create({
    index: indexName,
    mappings: {
      properties: {
        id: { type: 'integer' },
        title: {
          type: 'text',
          analyzer: 'standard',
          fields: { keyword: { type: 'keyword' } },
        },
        description: { type: 'text', analyzer: 'standard' },
        category: { type: 'keyword' },
        employment_type: { type: 'keyword' },
        location_city: { type: 'keyword' },
        location_country: { type: 'keyword' },
        is_remote: { type: 'boolean' },
        salary_min: { type: 'integer' },
        salary_max: { type: 'integer' },
        currency: { type: 'keyword' },
        status: { type: 'keyword' },
        published_at: { type: 'date' },
        company: {
          type: 'object',
          properties: {
            id: { type: 'integer' },
            name: {
              type: 'text',
              fields: { keyword: { type: 'keyword' } },
            },
            industry: { type: 'keyword' },
          },
        },
      },
    },
  });

  console.log(`Created index: ${indexName}`);
};
