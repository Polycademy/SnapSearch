Vagrant.configure("2") do |config|

    config.vm.box = "Saberce/ubuntu-trusty-64"

    config.ssh.forward_agent = true

    config.vm.network "forwarded_port", guest: 80, host: 8080

    config.vm.provider "virtualbox" do |vb|

        vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
        vb.customize ["modifyvm", :id, "--natdnsproxy1", "on"]

    end
  
end