pipeline {
    agent any
    
    environment {
        // Defines the docker image name (Local Image)
        IMAGE_NAME = 'gm_hms_dock'
        // We will skip pushing to a registry for a local setup
        // KUBECONFIG_CREDENTIALS_ID is still needed if your Jenkins needs to connect to K8s
        KUBECONFIG_CREDENTIALS_ID = 'kubeconfig-credentials'
    }

    stages {
        stage('Build Docker Image') {
            steps {
                script {
                    // Build the Docker image from the Dockerfile
                    dockerImage = docker.build("${IMAGE_NAME}:${env.BUILD_ID}")
                    // Also tag it as latest for the k8s deployment
                    docker.build("${IMAGE_NAME}:latest")
                }
            }
        }

        stage('Deploy to Kubernetes') {
            steps {
                // If Jenkins and Kubernetes are on the same machine (like Docker Desktop),
                // you might not even need withKubeConfig if permissions are right.
                // But we leave it here as standard practice.
                withKubeConfig([credentialsId: KUBECONFIG_CREDENTIALS_ID]) {
                    script {
                        // Update the image in the deployment to match the new build (Windows compatible)
                        def deployFile = 'k8s/gm-hms-deployment.yaml'
                        def fileContent = readFile(deployFile)
                        fileContent = fileContent.replaceAll(/image: gm_hms_dock:.*/, "image: ${IMAGE_NAME}:${BUILD_ID}")
                        writeFile(file: deployFile, text: fileContent)
                    }
                    
                    // Apply all Kubernetes manifests using Windows bat
                    bat 'kubectl apply -f k8s/'
                }
            }
        }
    }
    
    post {
        success {
            echo 'Pipeline executed successfully!'
        }
        failure {
            echo 'Pipeline failed. Please check the logs.'
        }
    }
}
