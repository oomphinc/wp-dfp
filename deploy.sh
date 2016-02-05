#! /bin/bash
# A modification of Dean Clatworthy's deploy script as found here: https://github.com/deanc/wordpress-plugin-git-svn
# The difference is that this script lives in the plugin's git repo & doesn't require an existing SVN repo.

# main config
PLUGINSLUG="wp-dfp"
BASEDIR=$(pwd)
MAINFILE="wp-dfp.php" # this should be the name of your main php file in the wordpress plugin
PLUGINPATH="$BASEDIR/wp-dfp"

# svn config
SVNPATH="/tmp/$PLUGINSLUG" # path to a temp SVN repo. No trailing slash required and don't add trunk.
SVNURL="http://plugins.svn.wordpress.org/wp-dfp" # Remote SVN repo on wordpress.org, with no trailing slash
SVNUSER="webgeekconsulting" # your svn username


# Let's begin...
echo ".........................................."
echo
echo "Preparing to deploy wordpress plugin"
echo
echo ".........................................."
echo

# Make sure build artifacts are cleaned
gulp clean

# Build
gulp build

# Check if subversion is installed before getting all worked up
if ! which svn >/dev/null; then
	echo "You'll need to install subversion before proceeding. Exiting....";
	exit 1;
fi

# Check version in readme.txt is the same as plugin file after translating both to unix line breaks to work around grep's failure to identify mac line breaks
NEWVERSION1=$(grep "^Stable tag:" $PLUGINPATH/readme.txt | awk -F' ' '{print $NF}')
echo "readme.txt version: $NEWVERSION1"
NEWVERSION2=$(grep "^Version:" $PLUGINPATH/$MAINFILE | awk -F' ' '{print $NF}')
echo "$MAINFILE version: $NEWVERSION2"

if [ "$NEWVERSION1" != "$NEWVERSION2" ]; then echo "Version in readme.txt & $MAINFILE don't match. Exiting...."; exit 1; fi

echo "Versions match in readme.txt and $MAINFILE. Let's proceed..."

if git show-ref --tags --quiet --verify -- "refs/tags/$NEWVERSION1"
	then
		echo "Version $NEWVERSION1 already exists as git tag. Exiting....";
		exit 1;
	else
		echo "Git version does not exist. Let's proceed..."
fi

cd "$BASEDIR" || (echo "Cannot cd into $BASEDIR" && exit 1)
echo -e "Enter a commit message for this new version: \c"
read -r COMMITMSG

if [ -n "$COMMITMSG" ]; then
	git commit -am "$COMMITMSG"
fi

echo "Tagging new version in git"
git tag -a "$NEWVERSION1" -m "Tagging version $NEWVERSION1"

echo "Pushing latest commit to origin, with tags"
git push origin master
git push origin master --tags

echo
echo "Creating local copy of SVN repo ..."
svn co "$SVNURL" "$SVNPATH"

echo "Clearing svn repo so we can overwrite it"
svn rm "$SVNPATH"/trunk/*

echo "Copying files to SVN"
cp -R "$PLUGINPATH"/* "$SVNPATH/trunk"

echo "Ignoring github specific files and deployment script"
svn propset svn:ignore ".git .gitignore" "$SVNPATH"/trunk/

echo "Changing directory to SVN and committing to trunk"
cd "$SVNPATH/trunk/" || (echo "Cannot cd into $SVNPATH/trunk" && exit 1)
# Add all new files that are not set to be ignored
svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}' | xargs svn add

if [ -z "$COMMITMSG" ]; then
	COMMITMSG="Adding version $NEWVERSION1"
fi

svn commit --username="$SVNUSER" -m "$COMMITMSG"

echo "Creating new SVN tag & committing it"
cd "$SVNPATH/trunk" || (echo "Cannot cd into $SVNPATH/trunk" && exit 1)
svn copy . "$SVNURL/tags/$NEWVERSION1/" --username="$SVNUSER" -m "Tagging version $NEWVERSION1"

echo "Cleaning up SVN assets folder"
svn rm "$SVNPATH"/assets/*

echo "Copying assets to SVN"
cd $BASEDIR || (echo "Cannot cd into $BASEDIR" && exit 1)
cp -R assets "$SVNPATH"

echo "Adding new assets to SVN"
cd "$SVNPATH"/assets || (echo "Cannot cd into $SVNPATH/assets" && exit 1)
svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}' | xargs svn add
svn commit --username="$SVNUSER" -m "Adding new assets"

echo "Removing temporary directory $SVNPATH"
rm -rf "$SVNPATH"

echo "*** FIN ***"
