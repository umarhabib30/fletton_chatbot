<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Delivery Failed - Action Required</title>
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
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            padding: 30px 40px;
            text-align: center;
            color: white;
        }

        .alert-icon {
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

        .status-card {
            background: #fff5f5;
            border: 2px solid #fed7d7;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 30px;
        }

        .status-title {
            color: #e53e3e;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }

        .status-title::before {
            content: "⚠️";
            margin-right: 10px;
            font-size: 20px;
        }

        .contact-info {
            background: #f7fafc;
            border-left: 4px solid #4299e1;
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
            background: linear-gradient(135deg, #4299e1, #3182ce);
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
            box-shadow: 0 8px 25px rgba(66, 153, 225, 0.3);
        }

        .footer {
            background: #2d3748;
            color: #a0aec0;
            padding: 24px 40px;
            text-align: center;
            font-size: 14px;
        }

        .priority-badge {
            display: inline-block;
            background: #fed7d7;
            color: #e53e3e;
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
            <div class="alert-icon">⚠️</div>
            <h1>Message Delivery Failed</h1>
            <p>Immediate attention required</p>
        </div>

        <div class="content">
            <div class="priority-badge">High Priority</div>

            <div class="status-card">
                <div class="status-title">
                    Failed to Send Message
                </div>
                <p>We were unable to deliver a message to the contact below. The automated response system has been disabled for this conversation to prevent further issues.</p>
            </div>

            <div class="contact-info">
                <h3>📋 Contact Information</h3>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value">{{ $data['first_name'] }} {{ $data['last_name'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value">{{ $data['contact'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">Auto-reply STOPPED</span>
                </div>
            </div>

            <div class="action-section">
                <h3>🚨 Action Required</h3>
                <p>Please log in to the admin panel to manually respond to this conversation and resolve any delivery issues.</p>
                <a href="{{ url('/') }}" class="cta-button">Login to Admin Panel</a>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated system notification. Please do not reply to this email.</p>
            <p>If you continue to experience issues, please contact technical support.</p>
        </div>
    </div>
</body>
</html>
