// WebAI Auditor API - Vercel Serverless Function
// Deploy on Vercel for free - connects to GitHub

export default function handler(request, response) {
  // Enable CORS
  response.setHeader('Access-Control-Allow-Origin', '*');
  response.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  response.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (request.method === 'OPTIONS') {
    return response.status(200).end();
  }

  const url = new URL(request.url, `http://${request.headers.host}`);
  const path = url.pathname.replace('/api', '');

  switch (path) {
    case '/health':
      return response.status(200).json({
        status: 'healthy',
        service: 'WebAI Auditor API',
        version: '1.0.0',
        timestamp: new Date().toISOString()
      });

    case '/stats':
      return response.status(200).json({
        total_audits: 15234,
        websites_analyzed: 8547,
        issues_found: 45234,
        happy_users: 5023
      });

    default:
      return response.status(404).json({
        error: 'Endpoint not found',
        available: ['/health', '/stats']
      });
  }
}
