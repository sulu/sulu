function header {
    echo ""
    echo -e "\x1b[32m======================================================\x1b[0m"
    echo $1
    echo -e "\x1b[32m======================================================\x1b[0m"
    echo ""
}

function info {
    echo -e "\x1b[32m"$1"\x1b[0m"

    echo ""
}

function comment {
    echo -e "\x1b[33m"$1"\x1b[0m"
    echo ""
}

function logo {
    cat <<EOT
   _____       _        _____ __  __ ______ 
  / ____|     | |      / ____|  \/  |  ____|
 | (___  _   _| |_   _| |    | \  / | |__   
  \___ \| | | | | | | | |    | |\/| |  __|  
  ____) | |_| | | |_| | |____| |  | | |     
 |_____/ \__,_|_|\__,_|\_____|_|  |_|_|     
                                            
EOT
}

function check_failed_tests {
    if [[ ! -s /tmp/failed.tests ]]; then
        # Everything was OK
        header "Everythig is AWESOME! \o/"
        exit 0
    else
        # There were failures
        echo ""
        echo -e "\x1b[31m======================================================\x1b[0m"
        echo "Oh no, "`cat /tmp/failed.tests | wc -l`" suite(s) failed:"
        echo ""
        for line in `cat /tmp/failed.tests`; do
            comment " - "$line
        done
        echo -e "\x1b[31m======================================================\x1b[0m"
        echo ""
        exit 1
    fi
}
