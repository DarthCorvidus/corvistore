properties(
	[
		parameters(
			[
				choice(choices: ['centos', 'debian'], description: 'Set the environment.', name: 'environment')
			]
		)
	]
)

node {
	def dockerId
	stage("Checkout from git") {
		git branch: 'main', credentialsId: 'git-token', url: 'https://github.com/DarthCorvidus/crow-protect.git'
	}
	
	stage("Create and launch podman image") {
		sh("podman build -t regression-${params.environment} ${WORKSPACE}/regression/${params.environment}/")
		dockerId = sh(script: "docker run -t -d -u 1000:1000 regression-${params.environment} cat", returnStdout: true).trim()
		println dockerId
	}
	
	stage("checkout from git") {
		println dockerId
		withCredentials([usernamePassword(credentialsId: 'git-token', passwordVariable: 'token', usernameVariable: 'user')]) {
			sh('podman exec --workdir /home/cpinst '+dockerId+' git clone https://$token@github.com/DarthCorvidus/crow-protect.git')
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
	}

	stage("Run Restore of /usr/bin/") {
		sh("podman exec --workdir /home/cpinst/crow-protect ${dockerId} sudo ./src/cpnc.php restore /usr/bin/ /root/restore")
	}

	stage("Stopping and removing container") {
		sh("podman stop ${dockerId}")
		sh("podman rm ${dockerId}")
	}
}