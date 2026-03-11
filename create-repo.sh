#!/bin/bash
# Create new GitHub repository using API
# Replace YOUR_GITHUB_TOKEN with your actual token
# Replace YOUR_USERNAME with your GitHub username

read -p "Enter your GitHub username: " USERNAME
read -p "Enter your GitHub Personal Access Token: " TOKEN
read -p "Enter new repository name: " REPO_NAME

echo "Creating repository: $REPO_NAME..."

curl -X POST \
  -H "Authorization: token $TOKEN" \
  -H "Accept: application/vnd.github.v3+json" \
  https://api.github.com/user/repos \
  -d "{\"name\":\"$REPO_NAME\",\"private\":false,\"description\":\"Comprehensive SEO Blog with 100,000+ words covering technical SEO, on-page optimization, link building, local SEO, and digital marketing strategies.\"}"

echo "Repository created!"
echo "Now run these commands to push:"
echo "git remote add new-origin https://github.com/$USERNAME/$REPO_NAME.git"
echo "git push -u new-origin master"
