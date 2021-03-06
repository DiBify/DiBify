DiBify [![Build Status](https://travis-ci.org/DiBify/DiBify.svg?branch=master)](https://travis-ci.org/DiBify/DiBify)
------------

DiBify - ORM система, которая может работать с несколькими разными базами данных одновременно, за счет того, что сама ORM
реализует внтури себя механизмы репликации и блокировок. С помощью DiBify вы можете использовать настоящий code-first
подход, т.к. в любой момент вы сможете без труда внедрить ваше приложение любую новую БД, которая возьмет на себя необходимые
функции. Например, к MySQL вы в любой момент сможете добавить ElasticSearch, а затем и Clickhouse или любую другую базу
не изменяя код вашего приложения. Все что нужно для подключения новой базы - это написать отдельный Storage, который будет
работать с этой базой, а также при необходимости Replicator, который будет отвечать за репликацию целостность данных.

DiBify невероятно гибкая ORM и может решить абсолютно любые задачи, однако если вашему приложению не требуется работа с
несколькими СУБД одновременно, то DiBify вероятно будет избыточным решением, и вам в таком случае стоит посмотреть на другие
ORM, например Doctrine