Simple site deploy with Hypernode Deploy
===============================================

In this repository I'm figuring out how to use [Hypernode Deploy](https://github.com/ByteInternet/hypernode-deploy-configuration) to deploy a simple PHP site to a Hypernode. This is a minimal example around the `pub/index.php` file.

Check out the Hypernode Deploy repositories [here](https://github.com/ByteInternet/hypernode-deploy-configuration) and [here](https://github.com/ByteInternet/hypernode-deploy).

# Prerequisites

To deploy this example you need:
- A disposable Hypernode server to mess around with
- Have it configured to run a modern PHP version like PHP 8.0 (run `$ hypernode-systemctl settings php_version 8.0` on that node)
- A public/private SSH keypair with which you have SSH access to the server (ssh app@yourhypernodeappname.hypernode.io)
- You need to have Docker installed on your computer

# How to deploy

First you need to perform the build step.
```
$ docker run --rm -it --env SSH_PRIVATE_KEY="$(cat ~/.ssh/yourdeploykey | base64)" -v ${PWD}:/build quay.io/hypernode/deploy:latest hypernode-deploy build -vvv
```

Then you can perform the actual deploy by running the deploy step:
```
docker run --rm -it --env SSH_PRIVATE_KEY="$(cat ~/.ssh/yourdeploykey | base64)" -v ${PWD}:/build quay.io/hypernode/deploy:latest hypernode-deploy deploy production -vvv
```

# Notes

If you're testing this and you're getting:
```
[production:yourhypernodeappname.hypernode.io] run cat /data/web/apps/yourhypernodeappname.hypernode.io/.dep/deploy.lock
[production:yourhypernodeappname.hypernode.io] root
[production:yourhypernodeappname.hypernode.io]  Deployer\Exception\GracefulShutdownException  in lock.php on line 14:
[production:yourhypernodeappname.hypernode.io]
[production:yourhypernodeappname.hypernode.io]   Deploy locked by root.
[production:yourhypernodeappname.hypernode.io]   Execute "deploy:unlock" task to unlock.
[production:yourhypernodeappname.hypernode.io]
[production:yourhypernodeappname.hypernode.io] #0 [internal function]: Hypernode\Deploy\Deployer\RecipeLoader->Deployer\{closure}()
[production:yourhypernodeappname.hypernode.io] #1 phar:///bin/hypernode-deploy/vendor/deployer/deployer/src/Task/Task.php(90): call_user_func()
[production:yourhypernodeappname.hypernode.io] #2 phar:///bin/hypernode-deploy/vendor/deployer/deployer/src/Executor/Worker.php(38): Deployer\Task\Task->run()
[production:yourhypernodeappname.hypernode.io] #3 phar:///bin/hypernode-deploy/vendor/deployer/deployer/src/Executor/Master.php(175): Deployer\Executor\Worker->execute()
[production:yourhypernodeappname.hypernode.io] #4 phar:///bin/hypernode-deploy/vendor/deployer/deployer/src/Executor/Master.php(128): Deployer\Executor\Master->runTask()
[production:yourhypernodeappname.hypernode.io] #5 phar:///bin/hypernode-deploy/src/DeployRunner.php(323): Deployer\Executor\Master->run()
[production:yourhypernodeappname.hypernode.io] #6 phar:///bin/hypernode-deploy/src/DeployRunner.php(96): Hypernode\Deploy\DeployRunner->runStage()
[production:yourhypernodeappname.hypernode.io] #7 phar:///bin/hypernode-deploy/src/Command/Deploy.php(41): Hypernode\Deploy\DeployRunner->run()
[production:yourhypernodeappname.hypernode.io] #8 phar:///bin/hypernode-deploy/vendor/symfony/console/Command/Command.php(298): Hypernode\Deploy\Command\Deploy->execute()
[production:yourhypernodeappname.hypernode.io] #9 phar:///bin/hypernode-deploy/vendor/symfony/console/Application.php(1028): Symfony\Component\Console\Command\Command->run()
[production:yourhypernodeappname.hypernode.io] #10 phar:///bin/hypernode-deploy/vendor/symfony/console/Application.php(299): Symfony\Component\Console\Application->doRunCommand()
[production:yourhypernodeappname.hypernode.io] #11 phar:///bin/hypernode-deploy/vendor/symfony/console/Application.php(171): Symfony\Component\Console\Application->doRun()
[production:yourhypernodeappname.hypernode.io] #12 phar:///bin/hypernode-deploy/src/Bootstrap.php(46): Symfony\Component\Console\Application->run()
[production:yourhypernodeappname.hypernode.io] #13 phar:///bin/hypernode-deploy/bin/hypernode-deploy.php(64): Hypernode\Deploy\Bootstrap->run()
[production:yourhypernodeappname.hypernode.io] #14 /bin/hypernode-deploy(12): require('...')
[production:yourhypernodeappname.hypernode.io] #15 {main}
```

You can log in to the Hypernode and `rm /data/web/apps/yourhypernodeappname.hypernode.io/.dep/deploy.lock` to unlock the deploy.
