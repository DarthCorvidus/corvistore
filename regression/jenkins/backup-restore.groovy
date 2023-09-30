properties([parameters([])])
properties(
	[
		parameters(
			[
				choice(choices: ['centos', 'debian'], description: 'Set the environment.', name: 'environment'),
				booleanParam(description: 'Use source from local directory.', name: 'local')
				
			]
		)
	]
)
// Change the display name to the selected environment.
currentBuild.displayName = params.environment
node {
	def dockerId
	try {
		stage("Checkout from git") {
			git branch: 'main', credentialsId: 'git-token', url: 'https://github.com/DarthCorvidus/crow-protect.git'
		}

		stage("Create and launch podman image") {
			sh("podman build -t regression-${params.environment} ${WORKSPACE}/regression/${params.environment}/")
			dockerId = sh(script: "docker run -t -d -u 1000:1000 regression-${params.environment} cat", returnStdout: true).trim()
			println dockerId
		}

		stage("checkout from git or copy from local") {
			println dockerId
			if(params.local) {
				sh('podman cp /home/jenkins/crow-protect/ '+dockerId+':/home/cpinst/crow-protect/ -av')
			} else {
				withCredentials([usernamePassword(credentialsId: 'git-token', passwordVariable: 'token', usernameVariable: 'user')]) {
					sh('podman exec --workdir /home/cpinst '+dockerId+' git clone https://$token@github.com/DarthCorvidus/crow-protect.git')
				}
			}
		}

		stage("Composer install") {
			sh("podman exec --workdir /home/cpinst/crow-protect ${dockerId} composer install")
		}

		stage("Composer Unit Test") {
			sh("podman exec --workdir /home/cpinst/crow-protect ${dockerId} composer run-script testdox")
		}

		stage("Initialize and configure server") {
			sh("podman exec --workdir /home/cpinst/crow-protect ${dockerId} ./src/cpserve.php --init=localhost")
			sh("podman exec --workdir /home/cpinst/crow-protect ${dockerId} ./src/cpserve.php --run-file=/home/cpinst/crow-protect/regression/init.run")
		}

		stage("Configure Backup client") {
			sh("podman exec --workdir /home/cpinst/crow-protect ${dockerId} sudo cp /home/cpinst/crow-protect/regression/client.conf /etc/crow-protect/client.conf")
			sh("podman exec --workdir /home/cpinst/crow-protect ${dockerId} sudo cp /home/cpinst/cpinst/ssl/server.crt /etc/crow-protect/")
			sh("podman exec --workdir /home/cpinst/crow-protect ${dockerId} sudo cp /home/cpinst/cpinst/ssl/ca.crt /etc/crow-protect/")
			sh("podman exec --workdir /home/cpinst/crow-protect ${dockerId} sudo cp /home/cpinst/crow-protect/regression/.crow-protect /root/")
		}

		stage("Starting server") {
			sh("docker exec -d --workdir /home/cpinst/crow-protect ${dockerId} ./src/cpserve.php")
			//Wait for the server to get up
			sleep 5
		}

		stage("Run Backup of /usr/bin/") {
			sh("podman exec --workdir /home/cpinst/crow-protect ${dockerId} sudo ./src/cpnc.php backup /usr/bin/")
			// Let the backup settle; due to the asynchronous nature of the receiving end,
			// it may happen that the backup is not yet done when restoring. A bug
			// to fix...
			sleep 30
		}

		stage("Run Restore of /usr/bin/") {
			sh("podman exec --workdir /home/cpinst/crow-protect ${dockerId} sudo ./src/cpnc.php restore /usr/bin/ /root/restore")
		}

		stage("Compare backup and restore") {
			sh("podman exec --workdir /home/cpinst/crow-protect ${dockerId} sudo ./regression/script/compare.php /usr/bin/ /root/restore/usr/bin/")
		}
	} catch (Exception e) {
		echo "Failed to run build steps: ${e.getMessage()}"
		currentBuild.result = 'FAILURE'
	} finally {
		sh("podman stop ${dockerId}")
		sh("podman rm ${dockerId}")
	}
}