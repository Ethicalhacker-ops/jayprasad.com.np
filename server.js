const http = require('http');
const fs = a = require('fs');
const path = require('path');
const fetch = require('node-fetch');
const dotenv = require('dotenv');

dotenv.config();

const PORT = process.env.PORT || 3000;

const server = http.createServer(async (req, res) => {
    const { method, url } = req;

    // Serve static files
    if (method === 'GET') {
        let filePath = path.join(__dirname, url === '/' ? 'index.html' : url);
        const extname = path.extname(filePath);
        let contentType = 'text/html';

        switch (extname) {
            case '.js':
                contentType = 'text/javascript';
                break;
            case '.css':
                contentType = 'text/css';
                break;
            case '.json':
                contentType = 'application/json';
                break;
            case '.png':
                contentType = 'image/png';
                break;
            case '.jpg':
                contentType = 'image/jpg';
                break;
        }

        fs.readFile(filePath, (err, content) => {
            if (err) {
                if (err.code == 'ENOENT') {
                    res.writeHead(404, { 'Content-Type': 'text/html' });
                    res.end('<h1>404 Not Found</h1>');
                } else {
                    res.writeHead(500);
                    res.end(`Server Error: ${err.code}`);
                }
            } else {
                res.writeHead(200, { 'Content-Type': contentType });
                res.end(content, 'utf-8');
            }
        });
        return;
    }

    // Handle chat API
    if (method === 'POST' && url === '/api/chat') {
        let body = '';
        req.on('data', chunk => {
            body += chunk.toString();
        });
        req.on('end', async () => {
            try {
                const { message } = JSON.parse(body);
                const apiKey = process.env.GEMINI_API_KEY;

                const geminiResponse = await fetch(
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-goog-api-key': apiKey
                        },
                        body: JSON.stringify({
                            contents: [
                                {
                                    parts: [
                                        {
                                            text: message
                                        }
                                    ]
                                }
                            ]
                        })
                    }
                );

                const data = await geminiResponse.json();
                const reply = data?.candidates?.[0]?.content?.parts?.[0]?.text || "No response from Gemini.";

                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ reply }));

            } catch (err) {
                console.error('API error:', err);
                res.writeHead(500, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ reply: 'Internal server error' }));
            }
        });
        return;
    }

    // Not found
    res.writeHead(404, { 'Content-Type': 'text/html' });
    res.end('<h1>404 Not Found</h1>');
});

server.listen(PORT, () => console.log(`✅ Server is running at http://localhost:${PORT}`));
