## **Environment setup**

This project is built with PHP framework [Laravel Zero](https://laravel-zero.com/). So PHP 8.1 should be installed on the local machine to build this project.  [Composer 2.3.7](https://getcomposer.org/) is also required to install all the dependecies of this project.


## **Usage**

After installing PHP and Composer, we need to install all the project dependecies. To do that in the terminal go to the <i>**animal-disease-cli**</i> project directory and run:
`composer update`.

Now the project is runnable. To list all commands: `php ad-cli -h`.

The main command is: `php ad-cli process:csv`. Running this will print all the needed arguments and their description.

This command takes two 3 arguments: 
- --cases-path:  The data cases file to process
- --diseases-path: The file containing disease list
- --output-path: The json file to store statistics

So the proper format of the command looks like: `php ad-cli process:csv --cases-path=/your-path/data_cases1.csv --diseases-path=/your-path/disease_list.csv --output-path=/your-path/indicators_1.json`.

## **How to build for production**

To build the project go to terminal and in the project directory run the following: `php ad-cli app:build ad-cli`.

This will build the project and the build file will be available inside **builds/** directory. Move the **ad-cli** file from build directory to another place and put that path to your environment variable. Then you can run **ad-cli** from anywhere without php suffix.


## **Source code**

All the business logic resides inside **app/Commands/ProcessCsv.php** file.