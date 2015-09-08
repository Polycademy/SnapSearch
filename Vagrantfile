Vagrant.configure("2") do |config|

    config.vm.box = "Saberce/ubuntu-trusty-64"

    config.vm.boot_timeout = 40

    config.ssh.forward_agent = true
    config.ssh.insert_key = false

    config.vm.network "forwarded_port", guest: 80, host: 8080
    config.vm.network "forwarded_port", guest: 443, host: 8081

    config.vm.network "private_network", type: "dhcp"

    config.vm.provider "virtualbox" do |vb|

        vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
        vb.customize ["modifyvm", :id, "--natdnsproxy1", "on"]

        # vb.gui = true

    end
  
end