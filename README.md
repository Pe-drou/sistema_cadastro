# sistema-cadastro




# CRIAÇÃO DA DATABASE USADA:

```sql
-- criar o banco de dados
create database sistema;

-- usar o banco de dados
use sistema;

-- criar uma tabela com o nome "usuarios"
create table usuarios(
	id int auto_increment primary key,
	nome varchar(100) not null,
    senha varchar(255) not null
);

-- criar uma tabela com o nome "fornecedores"
create table fornecedores (
	id int auto_increment primary key,
    nome varchar(100) not null,
    email varchar(100) not null,
    telefone varchar(20) not null
);

-- criar uma tabela com o nome "produtos", com uma FK (foreign key, chave estrangeira)
-- que conecta com os fornecedores
create table produtos (
	id int auto_increment primary key,
    fornecedor_id int,
    nome varchar(100) not null,
    descricao text,
    preco decimal(10, 2) not null,
    foreign key (fornecedor_id) references fornecedores(id)
);

-- inserindo dados na tabela usuário com senhas usando algorítimos de codificação
insert into usuarios (nome, senha) values ('Ignacio', MD5('123'));
insert into usuarios (nome, senha) values ('Valmir', MD5('123'));
insert into usuarios (nome, senha) values ('Nadja', MD5('123'));
insert into usuarios (nome, senha) values ('Raul', MD5('123'));
```