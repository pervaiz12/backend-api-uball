#!/bin/bash

# Setup Google OAuth Configuration for UBall Backend API
echo "🔧 Setting up Google OAuth configuration..."

# Check if .env file exists
if [ ! -f .env ]; then
    echo "📋 Creating .env file from .env.example..."
    cp .env.example .env
fi

# Add Google OAuth credentials to .env if they don't exist
if ! grep -q "GOOGLE_CLIENT_ID" .env; then
    echo "🔑 Adding Google OAuth credentials to .env..."
    echo "" >> .env
    echo "# Google OAuth Configuration" >> .env
    echo "GOOGLE_CLIENT_ID=add_client_id
GOOGLE_CLIENT_SECRET=GOOGLE_CLIENT_SECRET" >> .env
    echo "GOOGLE_REDIRECT_URI=http://localhost:5174/auth/google/callback" >> .env
    echo "✅ Google OAuth credentials added to .env"
else
    echo "✅ Google OAuth credentials already exist in .env"
fi

echo "🚀 Google OAuth setup complete!"
echo ""
echo "📝 Next steps:"
echo "1. Make sure your Laravel app is running: php artisan serve"
echo "2. Make sure your React app is running with the updated .env"
echo "3. Test the Google OAuth login flow"
