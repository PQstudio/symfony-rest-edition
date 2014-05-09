# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

ip = "192.168.0.42"


Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.vm.box = "wheezy64"
  config.vm.box_url = "http://puppet-vagrant-boxes.puppetlabs.com/debian-73-x64-virtualbox-puppet.box"
  config.vm.hostname = "mojlek.com.pl"

  # Assign this VM to a host-only network  IP, allowing you to access it
  # via the IP. Host-only networks can talk to the host machine as well as
  # any other machines on the same network, but cannot be accessed (through this
  # network interface) by any external networks.
  config.vm.network "private_network", ip: ip

  config.vm.provider "virtualbox" do |v|
      v.memory = 1024
  end

  # Forward a port from the guest to the host, which allows for outside
  # computers to access the VM, whereas host only networking does not.
  config.vm.network "forwarded_port", guest: 80, host: 3003

  # Enable Puppet
  config.vm.provision :puppet do |puppet|
      puppet.manifests_path = "../puppet-server-configuration/manifests"
      puppet.manifest_file  = "site.pp"
      puppet.module_path  = "../puppet-server-configuration/modules"
      puppet.options = "--verbose --debug"
  end

  config.vm.synced_folder "./", "/home/mojlek.com.pl/api/beta", type: "rsync",
      rsync__exclude: [".git/", "app/cache/", "app/logs/" ,"bin/", "build/"], rsync__args: ["--verbose", "-z", "--archive",
                                             "--chmod=Du=rwx,Dg=rwxs,Do=rx,Fu=rw,Fg=rw,Fo=r"], 
                                             :owner => "mojlek.com.pl", :group => "www-pub" 

      #:owner => "mojlek.com.pl", :group => "www-pub", 
      #:mount_options => ["dmode=2775","fmode=0664"]
      #:nfs => true, :map_uid => 0, :map_gid => 0


end
