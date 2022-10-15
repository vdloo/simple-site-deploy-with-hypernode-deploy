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


$configuration = new Configuration();
$configuration->addDeployTask('deploy:disable_public');
$configuration->addDeployTask('deploy:hmv_production');
$configuration->addDeployTask('deploy:hmv_staging');

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

return $configuration;
