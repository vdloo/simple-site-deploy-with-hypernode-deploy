<?php
namespace Hypernode\DeployConfiguration;

use function Deployer\run;
use function Deployer\task;
use function Deployer\currentHost;
use function Deployer\upload;

$APP_NAME = 'yourhypernodeappname';
$PROD_HOST = sprintf('%s.hypernode.io', $APP_NAME);
$STAG_HOST = sprintf('staging.%s.hypernode.io', $APP_NAME);
$PROD_WEBROOT = '/data/web/apps/yourhypernodeappname.hypernode.io/current/pub';
$STAG_WEBROOT = '/data/web/apps/staging.yourhypernodeappname.hypernode.io/current/pub';
$TEST_WEBROOT = '/data/web/apps/backend/current/pub';

# Disable the symlinking of /data/web/public because we're gonna be deploying both staging and prod on 1 Hypernode.
task('deploy:disable_public', function () {
    run("if ! test -d /data/web/public; then unlink /data/web/public; mkdir /data/web/public; fi");
    run("echo 'Not used, see /data/web/apps/ instead' > /data/web/public/index.html;");
});

# Configure SSL and the NGINX vhost for production if we're doing a production deploy
task('deploy:hmv_production', static function () use (&$PROD_HOST, &$PROD_WEBROOT) {
    if (currentHost()->getHostname() == $PROD_HOST) {
        run(sprintf('hypernode-manage-vhosts %s --https --force-https --type generic-php --yes --webroot %s', $PROD_HOST, $PROD_WEBROOT));
    }
});

# Configure SSL and the NGINX vhost for production if we're doing a staging deploy
task('deploy:hmv_staging', static function () use (&$STAG_HOST, &$STAG_WEBROOT) {
    if (currentHost()->getHostname() == $STAG_HOST) {
        run(sprintf('hypernode-manage-vhosts %s --https --force-https --type generic-php --yes --webroot %s', $STAG_HOST, $STAG_WEBROOT));
    }
});

# Configure SSL and the NGINX vhost for testing if we're doing an brancher deploy
# Note that this is a throw-away environment that is created on-demand by $testingStage->addBrancherServer($APP_NAME);
# As a copy of the latest backup snapshot of the Hypernode $APP_NAME
task('deploy:hmv_brancher', static function () use (&$STAG_HOST, &$PROD_HOST, &$TEST_WEBROOT) {
    if (currentHost()->getHostname() != $STAG_HOST && currentHost()->getHostname() != $PROD_HOST) {
        run(sprintf('hypernode-manage-vhosts $(jq -r .tag /etc/hypernode/app.json).$(jq -r .hn_domain /etc/hypernode/app.json) --https --force-https --type generic-php --yes --webroot %s', $TEST_WEBROOT));
    }
});


$configuration = new Configuration();
$configuration->addDeployTask('deploy:disable_public');
$configuration->addDeployTask('deploy:hmv_production');
$configuration->addDeployTask('deploy:hmv_staging');
$configuration->addDeployTask('deploy:hmv_brancher');

# Just some sane defaults to exclude from the deploy
$configuration->setDeployExclude([
    './.git',
    './.github',
    './deploy.php',
    './.gitlab-ci.yml',
    './Jenkinsfile',
    '.DS_Store',
    '.idea',
    '.gitignore',
    '.editorconfig',
    'etc/'
]);

$stagingStage = $configuration->addStage('staging', $STAG_HOST);
# Define the target server we're deploying staging to
$stagingStage->addServer($STAG_HOST);

$productionStage = $configuration->addStage('production', $PROD_HOST);
# Define the target server we're deploying production to
$productionStage->addServer($PROD_HOST);

# Because we don't really want to specify a 'Hostname' here because
# the real hostname is not known yet because it will be created by
# addBrancherServer during the deploy we specify 'backend' here.
# This is a 'host' in /etc/hosts on the Hypernode that just points
# to 127.0.0.1. Entering 'localhost' here would achieve the same
# but for clarity I use this different name here to indicate that
# we're not running this on the local machine but on the on-the-fly
# generated 'brancher' server.
$testingStage = $configuration->addStage('testing', 'backend');
# Define the brancher target server we're deploying testing to
$testingStage->addBrancherServer($APP_NAME);

return $configuration;
