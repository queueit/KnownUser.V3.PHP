# Run tests in Docker (VS code remote containers)
Prerequisite: 
- VS code extensions [Docker]("https://marketplace.visualstudio.com/items?itemName=ms-azuretools.vscode-docker") and [Remote - Containers]("https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers") is installed

1. Open VS Code and connect to container
2. Run command `php Tests/TestSuite.php`

# Debug Unit Tests in Docker (VS code remote containers)
1. Open VS Code and connect to container
2. Open Debug window (ctrl + shift + D)
3. Select the "Listen for Xdebug" configuration
4. Run the debugger
    - Here you might need to install the debugger extension for VS Code in your container.
5. Open the command terminal, within the container
6. Run "php Tests/TestSuite.php" (in the /workspace folder)


# Dockerfile in this folder
The dockerfile in this folder is used for executing tests in the CI pipeline