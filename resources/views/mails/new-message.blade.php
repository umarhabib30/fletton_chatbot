<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New WhatsApp Message Received</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.6;
            background-color: #f8f9fa;
            padding: 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #25D366, #128C7E);
            padding: 30px 40px;
            text-align: center;
            color: white;
        }

        .message-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .message-card {
            background: #f0f9ff;
            border: 2px solid #bae6fd;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 30px;
        }

        .message-title {
            color: #0369a1;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }

        .message-title::before {
            content: "💬";
            margin-right: 10px;
            font-size: 20px;
        }

        .contact-info {
            background: #f7fafc;
            border-left: 4px solid #25D366;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }

        .contact-info h3 {
            color: #2d3748;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: 600;
            color: #4a5568;
            min-width: 120px;
        }

        .info-value {
            color: #2d3748;
            background: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
        }

        .message-body {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            color: #2d3748;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .message-body-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #718096;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .action-section {
            background: #edf2f7;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            margin-top: 30px;
        }

        .action-section h3 {
            color: #2d3748;
            font-size: 18px;
            margin-bottom: 12px;
        }

        .action-section p {
            color: #4a5568;
            margin-bottom: 20px;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.3);
        }

        .footer {
            background: #2d3748;
            color: #a0aec0;
            padding: 24px 40px;
            text-align: center;
            font-size: 14px;
        }

        .notification-badge {
            display: inline-block;
            background: #bae6fd;
            color: #0369a1;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }

        @media (max-width: 600px) {
            body {
                padding: 10px;
            }

            .content {
                padding: 20px;
            }

            .header {
                padding: 20px;
            }

            .info-row {
                flex-direction: column;
            }

            .info-label {
                margin-bottom: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="message-icon">💬</div>
            <h1>New WhatsApp Message</h1>
            <p>You have received a new message</p>
        </div>

        <div class="content">
            <div class="notification-badge">New Message</div>

            <div class="message-card">
                <div class="message-title">
                    Message Received
                </div>
                <p>A new WhatsApp message has been received and stored in your chat history.</p>
            </div>

            <div class="contact-info">
                <h3>📱 Contact Information</h3>
                <div class="info-row">
                    <span class="info-label">Phone Number:</span>
                    <span class="info-value">{{ $data['friendlyName'] ?? 'N/A' }}</span>
                </div>
            </div>

            <div class="message-body">
                <div class="message-body-label">Message Content:</div>
                <div>{{ $data['body'] ?? 'No message content available' }}</div>
            </div>

            <div class="action-section">
                <h3>📬 View in Admin Panel</h3>
                <p>Log in to your admin panel to view the full conversation and respond to this message.</p>
                <a href="{{ url('/') }}" class="cta-button">View Messages</a>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated notification from your WhatsApp chatbot system.</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
