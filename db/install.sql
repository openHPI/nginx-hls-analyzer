-- $Id: install.sql 1 2009-09-15 20:13:42Z bvamos $

CREATE TABLE `fmslog` (                                                                                           
          `c-client-id` INT(11) DEFAULT NULL,                                                                             
          `c-ip` VARCHAR(15) CHARACTER SET latin1 DEFAULT NULL,                                                           
          `c-ip-country` VARCHAR(30) CHARACTER SET latin1 DEFAULT NULL,                                                   
          `x-file-name` VARCHAR(60) CHARACTER SET latin1 DEFAULT NULL,                                                    
          `x-sname` VARCHAR(40) CHARACTER SET latin1 DEFAULT NULL,                                                        
          `connect-timestamp` INT(11) DEFAULT NULL,                                                                       
          `disconnect-timestamp` INT(11) DEFAULT NULL,                                                                    
          `play-timestamp` INT(11) DEFAULT NULL,                                                                          
          `stop-timestamp` INT(11) DEFAULT NULL,                                                                          
          `pause-timestamp` INT(11) DEFAULT NULL,                                                                         
          `unpause-timestamp` INT(11) DEFAULT NULL,                                                                       
          `x-duration` INT(11) DEFAULT NULL,                                                                              
          `sc-bytes` INT(11) DEFAULT NULL,                                                                                
          `sc-stream-bytes` INT(11) DEFAULT NULL                                                                          
        ) ENGINE=MYISAM DEFAULT CHARSET=latin1;