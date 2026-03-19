#!/bin/bash

# Firebase Deployment Script for WebAI Audit
# Run with: bash deploy.sh

echo "===================================="
echo "WebAI Audit - Firebase Deployment"
echo "===================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if user is logged in
echo -n "Checking Firebase login status..."
if firebase projects:list >nul 2>&1; then
    echo -e " ${GREEN}OK${NC}"
else
    echo -e " ${RED}FAILED${NC}"
    echo ""
    echo -e "${YELLOW}Please run: firebase login${NC}"
    echo "Then run this script again."
    exit 1
fi

echo ""
echo "Current Firebase project:"
firebase projects:list
echo ""

# Navigate to script directory
cd "$(dirname "$0")"

echo "===================================="
echo "Deployment Options:"
echo "===================================="
echo ""
echo "1) Deploy Everything (Functions + Hosting + Firestore)"
echo "2) Deploy Functions Only"
echo "3) Deploy Hosting Only"
echo "4) Deploy Firestore Rules Only"
echo "5) Cancel"
echo ""

read -p "Select option (1-5): " choice

case $choice in
    1)
        echo ""
        echo "Deploying Everything..."
        firebase deploy
        ;;
    2)
        echo ""
        echo "Deploying Functions..."
        firebase deploy --only functions
        ;;
    3)
        echo ""
        echo "Deploying Hosting..."
        firebase deploy --only hosting
        ;;
    4)
        echo ""
        echo "Deploying Firestore Rules..."
        firebase deploy --only firestore:rules
        ;;
    5)
        echo "Deployment cancelled."
        exit 0
        ;;
    *)
        echo "Invalid option!"
        exit 1
        ;;
esac

echo ""
echo "===================================="
echo "Deployment Complete!"
echo "===================================="
echo ""
echo "Your app is live at:"
echo "- Hosting: https://webai-audit.web.app"
echo "- Functions: asia-south1-webai-audit.cloudfunctions.net"
echo ""
