HEAD_TAG=$(git tag --points-at HEAD | head -n 1);
LATEST_TAG=$(git describe --abbrev=0 --tags | head -n 1);
if [ "$HEAD_TAG" != "$LATEST_TAG" ]; then
  NEW_TAG="$LATEST_TAG"
  CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
  if [ "$CURRENT_BRANCH" = "HEAD" ]; then
    git checkout -b ACMS-000
    CURRENT_BRANCH=ACMS-000
  fi
  echo "$NEW_TAG, dev-$CURRENT_BRANCH"
  ALIAS="{\"dev-$CURRENT_BRANCH\": \"$NEW_TAG-dev\"}"
  composer config extra.branch-alias "$ALIAS" --json
fi;
