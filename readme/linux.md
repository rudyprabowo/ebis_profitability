# LINUX COMMAND
# synchronize to remote
rsync -avr --update --progress ./vendor/ -e "ssh -i /Users/rohimfikri/TMA/work/ppk/ism/idrsa_10.60.165.108_admapp" admapp@10.60.165.108:/app/vendor/