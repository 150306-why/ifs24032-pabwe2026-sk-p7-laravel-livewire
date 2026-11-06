pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
    }

    environment {
        COMPOSER_ALLOW_SUPERUSER = '1'
        APP_ENV = 'testing'
        XDEBUG_MODE = 'coverage'
    }

    stages {
        stage('Checkout') {
            steps { checkout scm }
        }

        stage('Install Dependencies') {
            steps {
                sh 'composer install --no-interaction --prefer-dist --no-progress'
                sh 'test -f .env || cp .env.example .env'
                sh 'php artisan key:generate --force'
                sh 'npm ci --ignore-scripts'
            }
        }

        stage('Composer Audit') {
            steps {
                // Gagal bila ditemukan kerentanan pada dependency.
                sh 'composer audit --locked'
            }
        }

        stage('Build Assets') {
            steps { sh 'npm run build' }
        }

        stage('Test & Coverage') {
            steps {
                sh 'vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml | tee coverage.txt'
                // Gate: Classes, Methods, dan Lines harus 100%.
                sh '''
                    for metric in Classes Methods Lines; do
                        line=$(grep -E "^\\s*${metric}:" coverage.txt | head -n1)
                        echo "$line"
                        echo "$line" | grep -q "100.00%" || { echo "Coverage ${metric} < 100%"; exit 1; }
                    done
                '''
            }
        }

        stage('Security Scan (Trivy)') {
            when { expression { sh(script: 'command -v trivy', returnStatus: true) == 0 } }
            steps {
                sh 'trivy fs --exit-code 1 --severity HIGH,CRITICAL --skip-dirs vendor,node_modules .'
            }
        }

        stage('Package') {
            steps {
                sh 'zip -r stock-things.zip . -x "vendor/*" "node_modules/*" ".git/*" ".env" "coverage*" "storage/logs/*"'
                archiveArtifacts artifacts: 'stock-things.zip', fingerprint: true
            }
        }
    }

    post {
        always { cleanWs() }
    }
}
