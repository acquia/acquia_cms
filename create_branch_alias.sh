HEAD_TAG=$(git tag --points-at HEAD | head -n 1);
LATEST_TAG=$(git describe --abbrev=0 --tags | head -n 1);
NEW_TAG="$(echo $LATEST_TAG | sed 's/\-.*//')"
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
echo "$NEW_TAG, dev-$CURRENT_BRANCH"
ALIAS="{\"dev-$CURRENT_BRANCH\": \"$NEW_TAG-dev\"}"
composer config extra.branch-alias "$ALIAS" --json
