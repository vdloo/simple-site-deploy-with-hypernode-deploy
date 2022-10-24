<?php
namespace Hypernode\DeployConfiguration;

use function Deployer\run;
use function Deployer\task;
use function Deployer\currentHost;
use function Deployer\upload;

$APP_NAME = 'yourhypernodeappname';
$PROD_HOST = sprintf('%s.hypernode.io', $APP_NAME);
$STAG_HOST = sprintf('staging.%s.hypernode.io', $APP_NAME);
$DOCKER_HOST = '172.17.0.2';
$PROD_WEBROOT = '/data/web/apps/yourhypernodeappname.hypernode.io/current/pub';
$STAG_WEBROOT = '/data/web/apps/staging.yourhypernodeappname.hypernode.io/current/pub';
$TEST_WEBROOT = '/data/web/apps/backend/current/pub';
$DOCKER_WEBROOT = '/data/web/apps/172.17.0.2/current/pub';

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
        run(sprintf('if ! test -f /etc/hypernode/is_docker; then hypernode-manage-vhosts $(jq -r .tag /etc/hypernode/app.json).$(jq -r .hn_domain /etc/hypernode/app.json) --https --force-https --type generic-php --yes --webroot %s; fi', $TEST_WEBROOT));
    }
});

# HMV configuration for when this is running in a docker
task('deploy:hmv_docker', static function () use (&$DOCKER_HOST, &$DOCKER_WEBROOT) {
    run(sprintf('if test -f /etc/hypernode/is_docker; then hypernode-manage-vhosts %s --disable-https --type generic-php --yes --webroot %s --default-server; fi', $DOCKER_HOST, $DOCKER_WEBROOT));
});


$configuration = new Configuration();
$configuration->addDeployTask('deploy:disable_public');
$configuration->addDeployTask('deploy:hmv_production');
$configuration->addDeployTask('deploy:hmv_staging');
$configuration->addDeployTask('deploy:hmv_brancher');
$configuration->addDeployTask('deploy:hmv_docker');

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

# We can also deploy to a Hypernode Docker instance. To do that you go to
# https://github.com/byteinternet/hypernode-docker, make sure you
# have an instance running by for example doing:
# $ sudo docker run -P docker.hypernode.com/byteinternet/hypernode-buster-docker-php80-mysql57:latest
# and then noting the IP address (in my case 172.17.0.2). You then
# need to make sure your deploykey public key is added to the
# /data/web/.ssh/authorized_keys file. Then you should be able to
# deploy to the container as if it was a 'real' hypernode. Keep in
# mind that the hypernode-docker is not a real VM, it's just a fat
# container. This means that there won't be an init system (no systemd)
# so the processes are running in SCREENs. Also obviously you can not use
# some of the hypernode command-line functionality that depends on the
# Hypernode API (it's not a server managed by the Hypernode automation,
# just a local container running on your PC).
$dockerStage = $configuration->addStage('docker', $DOCKER_HOST);
# Define the target server (docker instance) we're deploying to
$dockerStage->addServer($DOCKER_HOST);

return $configuration;
