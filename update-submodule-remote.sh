PUSH_URL=$(git remote get-url --push origin)
if [[ "$PUSH_URL" == https* ]]; then
  SSH_URL=$(git remote get-url --push origin | sed 's/https:\/\/.*project/git@git.drupal.org:project/')
  git remote set-url --add --push origin $SSH_URL
fi
