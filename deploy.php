<?php
namespace Hypernode\DeployConfiguration;

use function Deployer\run;
use function Deployer\task;
use function Deployer\upload;

$APP_NAME = 'yourhypernodeappname';

# Disable deploy:vendors. I'm not really deploying Magento, just a PHP file.
task('deploy:vendors', static function () {});

# Set up letsencrypt for appname.hypernode.io and enforce https
task('deploy:hmv', static function () use (&$APP_NAME) {
    run(sprintf('hypernode-manage-vhosts %s.hypernode.io --https --force-https --type generic-php --yes', $APP_NAME));
});

$configuration = new Configuration();
$configuration->addDeployTask('deploy:hmv');
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

$productionStage = $configuration->addStage('production', sprintf('%s.hypernode.io', $APP_NAME));
# Define the target server we're deploying to
$productionStage->addServer(sprintf('%s.hypernode.io', $APP_NAME));

return $configuration;
