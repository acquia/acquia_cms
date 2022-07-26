HEAD_TAG=$(git tag --points-at HEAD | head -n 1);
LATEST_TAG=$(git describe --abbrev=0 --tags | head -n 1);
#if [ "$HEAD_TAG" != "$LATEST_TAG" ]; then
  NEW_TAG="$LATEST_TAG"

  # Remove the -alpha-N or -beta-N or -rc-N etc. where N is number.
  NEW_TAG="$(echo $NEW_TAG | sed 's/\-.*//')"
  CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
  echo "The current Branch is: $CURRENT_BRANCH"
  if [ "$CURRENT_BRANCH" = "HEAD" ]; then
    MODULE=$(pwd | sed 's/.*\///')
    if [ "$MODULE" = "acquia_cms_common" ]; then
      #git fetch origin develop-1.4.x
      git checkout -b develop-1.4.x
    elif [ "$MODULE" = "acquia_cms_tour" ]; then
       # git fetch origin develop-2.0.x
        git checkout -b develop-2.0.x
    else
      #git fetch origin develop
      git checkout -b develop
    fi
    CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
  fi
  echo "$NEW_TAG, dev-$CURRENT_BRANCH"
  ALIAS="{\"dev-$CURRENT_BRANCH\": \"$NEW_TAG-dev\"}"
  echo "The Alias is: $ALIAS"
  composer config extra.branch-alias "$ALIAS" --json
#fi;
