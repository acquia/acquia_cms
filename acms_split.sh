#!/usr/bin/env bash

# Color codes
GREEN="\033[1;32m"
YELLOW="\033[1;33m"
NOCOLOR="\033[0m"

# Debug mode
set -e
set -x

echo -e "${GREEN} Script Running on ${OSTYPE}${NOCOLOR}"

# Sample usage: ./runsplit.sh
# Commit will be traversed/updated from source to destination repositary.

# Dynamically setting current branch to checked out branch.
# For example if this script is run on branch, test then,
# commit will traverse from test branch of source repo to,
# test branch of destination repo.
CURRENT_BRANCH=`git rev-parse --abbrev-ref HEAD`

echo -e "${YELLOW} Script running for split on branch $CURRENT_BRANCH${NOCOLOR}"

# Split method to run the commands for splitting/updating a split and pushing,
# any new commits.
# $1 is path of module, $2 is name of remote branch for the split.
function split()
{
    SHA1=`./splitsh-lite --prefix=$1`
    git push $2 "$SHA1:refs/heads/$CURRENT_BRANCH" -f
}

# Remote method, to add git remote for destination repositary.
# $1 is subrepo name and $2 is remote branch name.
function remote()
{
    git remote add $1 $2 || true
}

# Pull current branch.
git pull origin $CURRENT_BRANCH

# Adding remote for all split branches.
remote acquia_cms_article git@github.com:acquia/acquia_cms_article.git
remote acquia_cms_audio git@github.com:acquia/acquia_cms_audio.git
remote acquia_cms_common git@github.com:acquia/acquia_cms_common.git
remote acquia_cms_development git@github.com:acquia/acquia_cms_development.git
remote acquia_cms_document git@github.com:acquia/acquia_cms_document.git
remote acquia_cms_event git@github.com:acquia/acquia_cms_event.git
remote acquia_cms_image git@github.com:acquia/acquia_cms_image.git
remote acquia_cms_page git@github.com:acquia/acquia_cms_page.git
remote acquia_cms_person git@github.com:acquia/acquia_cms_person.git
remote acquia_cms_place git@github.com:acquia/acquia_cms_place.git
remote acquia_cms_search git@github.com:acquia/acquia_cms_search.git
remote acquia_cms_starter git@github.com:acquia/acquia_cms_starter.git
remote acquia_cms_support git@github.com:acquia/acquia_cms_support.git
remote acquia_cms_toolbar git@github.com:acquia/acquia_cms_toolbar.git
remote acquia_cms_tour git@github.com:acquia/acquia_cms_tour.git
remote acquia_cms_video git@github.com:acquia/acquia_cms_video.git

# Calling split method for mapping remote branches to splits.
split 'modules/acquia_cms_article' acquia_cms_article
split 'modules/acquia_cms_audio' acquia_cms_audio
split 'modules/acquia_cms_common' acquia_cms_common
split 'modules/acquia_cms_development' acquia_cms_development
split 'modules/acquia_cms_document' acquia_cms_document
split 'modules/acquia_cms_event' acquia_cms_event
split 'modules/acquia_cms_image' acquia_cms_image
split 'modules/acquia_cms_page' acquia_cms_page
split 'modules/acquia_cms_person' acquia_cms_person
split 'modules/acquia_cms_place' acquia_cms_place
split 'modules/acquia_cms_search' acquia_cms_search
split 'modules/acquia_cms_starter' acquia_cms_starter
split 'modules/acquia_cms_support' acquia_cms_support
split 'modules/acquia_cms_toolbar' acquia_cms_toolbar
split 'modules/acquia_cms_tour' acquia_cms_tour
split 'modules/acquia_cms_video' acquia_cms_video

