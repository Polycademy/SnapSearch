Vagrant.configure("2") do |config|

    config.vm.box = "Saberce/ubuntu-trusty-64"

    config.vm.network "forwarded_port", guest: 80, host: 8080

    # test private network
    #config.vm.network "private_network", type: "dhcp"

    config.vm.synced_folder "./", "/snapsearch"

    # config.vm.provider "virtualbox" do |vb|
    #   # Use VBoxManage to customize the VM. For example to change memory:
    #   vb.customize ["modifyvm", :id, "--memory", "1024"]
    # end
  
end
