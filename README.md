YOUSDR v0.1 Alpha - RTL-SDR webinterface with installation instructions for Raspbian / Raspberry PI.

This is a web-wrapper for the rtl-sdr softwares.
Currently it commands rtl_fm, rtl_tcp and dump1090

The method of using system() to run commands is quite unclean so this is ery much work in progress.
Please feel free to provide improvements!

If you are reading this on GITHub, to install on your Raspbian system, clone this directory to /var/www/yousdr, .i.e:

```
cd /var/www
git clone http://github.com/sixuniform/yousdr.git
```

------------ 
PREREQUISITES 
------------ 
Install a fresh raspbian on your PI.
If you run on a Raspberry PI B (512Mb) you should benefit from choosing overclock options of at least 950 MHz (arm_freq=950, core_freq=250, sdram_freq=450, over_voltage=6).
Mine runs fine (gpu_mem=16,gpu_freq=100,avoid_pwm_pll=1,arm_freq=1000,sdram_freq=600,core_freq=400,over_voltage=6,over_voltage_sdram=3,force_turbo=0)

Login as "pi" password "raspberry" (or whatever you changed it to). Sudo to become root:
```
sudo bash
```

Disable the regular DVB-T drivers to be loaded for our stick by running:
```
echo "blacklist dvb_usb_rtl28xxu" >/etc/modprobe.d/blacklist-dvb_usb_rtl28xxu.conf
```

Install apache, php, mysql, darkice and icecast. Answer yes to any additional packages:

```
apt-get install mysql-server mysql-client	# Set MySQL root password (write it down).
apt-get install apache2
apt-get install php5 php5-mysql
apt-get install icecast2   			# Answer "No" if icecast asks you to configure it.
apt-get install screen    			# Optional, but great for command-line multitasking
```

Unfortunately, darkice doesn't come with ALSA support. Probably an outdated package. To fix this, you can follow these instructions to compile your own:
```
http://mattkaar.com/blog/2013/05/26/web-streaming-with-the-raspberry-pi-baby-monitor/
```

Install RTL-SDR and DUMP1090. There's excellent information on that here, you can jump down to point "8." and start there:
```
http://www.satsignal.eu/raspberry-pi/dump1090.html#RTL
```

After making dump1090 you need to install it manually. Stay in the directory after "make" completes, and run: 
```
cp -r public_html/ /var/www/
cp dump1090 /usr/local/bin/
```

Start MySQL to create the database and setup the tables:

```
mysql -u root -p   # (enter the password you gave during the MySQL installation process)
```

You may replace "sdruser" and "sdrpassword" with your own preferred username and password and write them down. These will be entered in "config.php" later on!

Type:

```
create database sdr;
use sdr;

CREATE TABLE `actions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `freq` varchar(15) DEFAULT NULL,
  `bw` int(11) DEFAULT NULL,
  `autobw` tinyint(1) DEFAULT NULL,
  `squl` int(3) DEFAULT NULL,
  `mode` varchar(10) DEFAULT NULL,
  `ppm` int(5) DEFAULT NULL,
  `filter` varchar(10) DEFAULT NULL,
  `user` varchar(20) DEFAULT NULL,
  `time` int(10) unsigned DEFAULT NULL,
  `rxmode` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
);

grant all on sdr.* to 'sdruser'@'%' identified by 'sdrpassword';

quit
```

Now we're done with MySQL tables.

Make sure the apache user has access to the sound cards by editing /etc/groups and add ",www-data" after the "audio" group. It should look something like this:
```
audio:x:29:pi,www-data
```

You need to add the alsa loopback audio module on startup, edit /etc/modules and add a new line reading "snd-aloop".

Raspbian apache runs as www-data by default. If your apache server is run by some other user you need to add that user instead of "www-data".

Now we need to set up a loopback sound interface, either copy the included contrib/asound.conf to /etc/asound.conf
or manually edit /etc/asound.conf and add the needed sections to your existing config.

This directoty (yousdr) should be moved to /var/www/yousdr so it can be accessed on http://your-hostname/yousdr

From the yousdr-directory, copy contrib/icecast.xml to /etc/icecast2/icecast.xml:
```
cp contrib/icecast.xml /etc/icecast2/icecast.xml
```

Also, copy contrib/darkice.cfg /etc/darkice.cfg:
```
cp contrib/darkice.cfg /etc/darkice.cfg
```

Edit "/etc/default/icecast2" and change "ENABLE=false" to "ENABLE=true"
Edit "/etc/default/darkice" and change "RUN=no" to "RUN=yes"

Good luck,
 Rick - SM6U / SM6YOU
