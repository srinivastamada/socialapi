# socialapi
PHP API for social Oauth login


CREATE TABLE `users` (
  `uid` int(11) primary key auto_increment,
  `name` varchar(200) DEFAULT NULL,
  `token` varchar(400) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `provider` varchar(30) DEFAULT NULL,
  `provider_id` varchar(300) DEFAULT NULL,
  `provider_pic` varchar(300) DEFAULT NULL
) 
