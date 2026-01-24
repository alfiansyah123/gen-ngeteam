const pool = require('./utils/db');

exports.handler = async (event, context) => {
    // CORS headers
    const headers = {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Headers': 'Content-Type',
        'Content-Type': 'application/json'
    };

    // Handle preflight
    if (event.httpMethod === 'OPTIONS') {
        return { statusCode: 200, headers, body: '' };
    }

    try {
        const result = await pool.query('SELECT url FROM domains WHERE active = true ORDER BY url');
        const domains = result.rows.map(row => row.url);

        return {
            statusCode: 200,
            headers,
            body: JSON.stringify({ domains })
        };
    } catch (error) {
        console.error('Database error:', error);
        return {
            statusCode: 500,
            headers,
            body: JSON.stringify({ error: 'Failed to fetch domains: ' + error.message })
        };
    }
};
