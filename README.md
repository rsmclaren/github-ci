# Setting up a Github Actions Pipeline

## Required Resources
- VSCode
- Git insalled on machine
- Github account
- Github SSH Key
- AWS Academy account

## Server Setup and Configuration
- Log into [AWS Academy](https://awsacademy.instructure.com/)
- Navigate to Courses and select the AWS Academy Learner Lab
- Navigate to Modules and click the "Launch AWS Academy Learner Lab" button
- Click the Home button
- Click on the first link in the table
- Click the "Start Lab" button
- Click the "AWS" link after the circle turns green
- In the Search box at the top of the screen click "Services" and type "EC2"
- Click the "EC2" link
- Click the "Launch instance" button
- Give your instance the name "Github-Actions"
- Select Ubuntu for the Application and OS Images
- Select the t2.micro instance type
- For the Key pair (login) click the "Create a new key pair" button
- Set the key pair name to "Github-Actions"
- Use the "pem" (Privacy Enhanced Mail) format
- Click Create key pair and save the file to your machine
- Check "Allow HTTP traffic" and "Allow HTTPS traffic"
- Click the "Launch instance" button

## Connect to your EC2 instance
- After the instance is created click the "Connect to instance" button
- Click the "SSH client" tab
- Copy the ssh command near the bottom just under where it says "Example"
- Open a terminal window on your machine in the directory where you saved the pem file
- Paste the ssh command into the terminal window and press enter

## Server Configuration
- We need to install web server software and PHP on our EC2 instance
- We will use Apache as our web server software
- To install Apache run the following commands in your terminal window

```bash
sudo apt update
sudo apt install apache2
sudo a2enmod rewrite
```
- Now, we need to install php and composer (composer is a php package manager)

```bash
sudo add-apt-repository ppa:ondrej/php

sudo apt update

sudo apt install php8.2 -y

sudo apt install composer

sudo apt install php8.2-xml
```
- ensure php is installed

```bash
php --version
```
- We need to make a small configuration change to apache to allow us to use .htaccess files, specifically in the default vhost file

```bash
sudo nano /etc/apache2/sites-available/000-default.conf
```
- add the following inside of the `<VirtualHost *:80>` tag
  
```bash
<Directory /var/www/>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

- restart apache

```bash
sudo systemctl restart apache2
```
- we need to update the permissions on the /var/www/html directory so the ubuntu user can write to it

```bash
sudo chown -R ubuntu:ubuntu /var/www/html
```

- Navigate to the IPv4 address of your EC2 instance in a browser and you should see the default apache page (ensure you are not using https)
- Lastly, we need to delete the default index.html file

```bash
sudo rm /var/www/html/index.html
```

## Create Fork and Clone to Local Machine
- Create a fork of this repo and clone it down to your machine
- You are going to add the required github actions to this repo to create a pipeline that will run your tests and deploy your code to your EC2 instance

## Adding our workflow
- we're going to define a workflow in a yml file (stands for yet another markup language) in your repo
- in the `.github/workflows/ci.yml` file we will define the actions we want to run. Copy and paste the below snippet into your ci.yml file

```yml
# first we define our action name 
name: CI

# Trigger deployment only on push to main branch, and allow manual triggering
on:
  push:
    branches:
      - main
  workflow_dispatch:

# now we define the jobs we want to run - this section defines the jobs that will run as part of the workflow
jobs:
  build-test:
    # name is the human friendly name for the job
    name: Run Unit Tests
    # this is the type of virtual machine that the job will run on - jobs are handled by "runners", which is just a # virtual machine. Github hosts runners, which will we utilize, but it is possible to use self hosted runners # as well
    runs-on: ubuntu-latest
    # our jobs contains a sequence of actions, known as steps          
    steps:
      # uses - we are specifiying an action to run as part of our step. actions are custom applications that perform complex but frequently repeated task. this action will checkout our repository code to the runner
      - uses: actions/checkout@v3

      # this step will install composer dependencies (composer is a php package manager)
      - uses: php-actions/composer@v6

      # this step will run our phpunit tests
      - name: PHPUnit Tests
        uses: php-actions/phpunit@master
        env:
          TEST_NAME: Scarlett
        with:
          version: 9.6
          bootstrap: vendor/autoload.php
          configuration: test/phpunit.xml
          args: --coverage-text
```

- commit and push your changes to Github
- navigate to the actions tab of your repo on Github and you should see your workflow running and complete successfully

## Adding our deploy job
- We've configured our workflow to run our tests on every push to the main branch. Now we want to deploy our code to our EC2 instance when our tests pass
- Before we update our workflow file, we need to configure some secrets in our repo on Github
- Navigate to the settings tab of your repo, and select secrets > actions. click new repository secret button
- add the following 4 secrets:
  
| Name        | Secret                                    |
|-------------|-------------------------------------------|
| EC2_SSH_KEY | copy/paste the contents of your .pem file |
| REMOTE_HOST | copy/paste your EC2s IPv4 address         |
| REMOTE_USER | ubuntu                                    |
| TARGET      | var/www/html                              |

- add the following to your workflow file, tabbed under the `jobs` section
- the tabbing of the code is important!!
  
```yml
# this job will deploy our code to our EC2 instance
deploy:
  name: Deploy to EC2 on master branch push
  runs-on: ubuntu-latest
  needs: build-test  # This ensures the "deploy" job depends on "build-test" job
  if: success()     # This ensures "deploy" job runs only if "build-test" is successful
  steps:
    - name: Checkout the files
      uses: actions/checkout@v3
      
    - name: Deploy to Server
      # this actions uses the rsync command to copy files from the runner to the ec2 server
      uses: easingthemes/ssh-deploy@main
      # the runner needs to connect to our EC2 server so its needs to know the host, username, and key file so it can connect. It is using the values we defined as secrets in the previous step
      env: 
        SSH_PRIVATE_KEY: ${{secrets.EC2_SSH_KEY}}
        REMOTE_HOST: ${{secrets.HOST_DNS}}
        REMOTE_USER: ${{secrets.USERNAME}}
        TARGET: ${{secrets.TARGET_DIR}}
# this job will run composer install on our EC2 instance
composer:
  name: Run composer install on EC2 instance
  runs-on: ubuntu-latest
  needs: deploy
  if: success()
  steps:
    - name: execute composer install
      uses: appleboy/ssh-action@v1.0.0
      with:
        host: ${{secrets.HOST_DNS}}
        username: ${{secrets.USERNAME}}
        key: ${{secrets.EC2_SSH_KEY}}
        script: |
          cd ${{secrets.TARGET_DIR}}
          composer install
```

- Commit your changes and push/sync your code to GitHub
- Navigate to the actions tab of your repo and you should see your workflow run tests, deploy your code, and run composer install on your EC2 instance
- Navigate to the IPv4 address of your EC2 instance in a browser and you should see the simple app displayed
- Let's introduce a bug into our code to see what happens when our tests fail
- update line 30 in `src/Email.php` with the following

```php
if (!filter_var($email, FILTER_VALIDATE_IP)) {
```

- this will cause our tests to fail because we are validating an email address as an IP address
- commit and push your changes to Github
- navigate to the actions tab of your repo and you should see your workflow run but this time the tests will fail and the other steps will not run
- You will also receive an email from Github notifying you that your tests failed