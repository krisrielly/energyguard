nohup python write_power_consumption.py > write_power_consumption.out &

add a cronjab "crontab -e"
*/1 * * * * cd /home/pi/EnergyGuard && sh data_cloud_upload.sh >> data_cloud_upload.out &

restart cron

/etc/init.d/cron reload
