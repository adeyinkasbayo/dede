#!/bin/bash

# GitHub Push Script for Darfiden Management System
# Repository: https://github.com/adeyinkasbayo/dede

echo "======================================"
echo "GitHub Push Script"
echo "======================================"
echo ""
echo "You will need:"
echo "- GitHub username: adeyinkasbayo"
echo "- Personal Access Token (NOT password)"
echo ""
echo "Get your token at: https://github.com/settings/tokens"
echo ""
echo "======================================"
echo ""

cd /app/public_html

# Check if we're in the right directory
if [ ! -d ".git" ]; then
    echo "ERROR: Not a git repository!"
    exit 1
fi

# Show current status
echo "Current status:"
git status
echo ""

# Attempt to push
echo "Attempting to push to GitHub..."
echo "You will be prompted for username and password (use token as password)"
echo ""

git push -u origin main

if [ $? -eq 0 ]; then
    echo ""
    echo "======================================"
    echo "✅ SUCCESS! Files pushed to GitHub!"
    echo "======================================"
    echo ""
    echo "Your repository is now available at:"
    echo "https://github.com/adeyinkasbayo/dede"
    echo ""
else
    echo ""
    echo "======================================"
    echo "❌ Push failed. Possible reasons:"
    echo "======================================"
    echo "1. Invalid credentials"
    echo "2. Need Personal Access Token (not password)"
    echo "3. Repository doesn't exist"
    echo "4. No internet connection"
    echo ""
    echo "Get token: https://github.com/settings/tokens"
    echo ""
fi
