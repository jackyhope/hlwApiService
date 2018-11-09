;<?php if(ENV !== 'online'): ?>
[newexam]
main   =   host:192.168.118.16,port:3306,user:root,database:newexam,password:123456,charset:utf8
query  =   host:192.168.118.16,port:3306,user:root,database:newexam,password:123456,charset:utf8
<?php else:?>
[newexam]
main   =   host:10.30.88.15,port:3306,user:SAP,database:newexam,password:bffebfb01900fe3af8a8633d3b0b7be2,charset:utf8
query  =   host:10.30.88.15,port:3306,user:SAP,database:newexam,password:bffebfb01900fe3af8a8633d3b0b7be2,charset:utf8
<?php endif;exit(); ?>
