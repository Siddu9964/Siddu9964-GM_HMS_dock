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
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

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
                    sh '''
                        # Update the image in the deployment to match the new build
                        # Notice we removed the registry prefix since it's a local image
                        sed -i "s|image: gm_hms_dock:.*|image: ${IMAGE_NAME}:${BUILD_ID}|g" k8s/gm-hms-deployment.yaml
                        
                        # Apply all Kubernetes manifests (DB and Web App)
                        kubectl apply -f k8s/
                    '''
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
