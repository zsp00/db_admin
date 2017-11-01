-- 流程主表
create table d_process
(
  id                   int                       auto_increment primary key,
  name                 varchar(200)              not null            comment '流程名称',
  deptNo               varchar(8)                not null            comment '所属部门Id',
  level                tinyint                   not null            comment '流程的步骤数量',
  creator              varchar(10)               not null            comment '流程创建者',
  createTime           int                       not null,
  updateTime           int                       not null,
  status               boolean                   not null
);


-- 流程副表(流程快照)
create table d_process_vice
(
  id                   int                       auto_increment primary key,
  pId                  int                       not null,
  name                 varchar(200)              not null            comment '流程名称',
  deptNo               varchar(8)                not null            comment '所属部门Id',
  level                tinyint                   not null            comment '流程的步骤数量',
  creator              varchar(10)               not null            comment '流程创建者'
);


-- 流程子表
create table d_process_data
(
  id                   int                       auto_increment primary key,
  pId                  int                       not null            comment '流程Id',
  levelNo              tinyint                   not null            comment '流程第几步',
  audit_user           varchar(2000)             not null            comment '参与到该步流程的人员、部门，json格式',
  pDescribe             varchar(200)              not null            comment '流程步骤描述'
);


-- 任务分类表
create table d_task_type
(
  id                   int                       auto_increment primary key,
  typeName             varchar(100)              not null,
  creator              varchar(10)               not null,
  createTime           int                       not null,
  updateTime           int                       not null,
  status               boolean                   not null
);



-- 任务分类对关系表(支持一对多)
create table d_task_tasktype
(
  id                   int                       auto_increment primary key,
  tId                  int                       not null,
  typeId               int                       not null
);

-- 修改现有表结构

-- 任务表
CREATE TABLE d_task
(
  id                   int                       AUTO_INCREMENT primary key,
  deptNo               varchar(255)              NOT NULL            COMMENT '组织编号',
  serialNumber         int                       DEFAULT 0           COMMENT '序号',
  content              varchar(500)              NOT NULL            COMMENT '任务',
  releaseTime          int                       NOT NULL            COMMENT '发布时间，时间戳',
  timeLimit            mediumint                 NOT NULL            COMMENT '时限',
  level                enum('D','C','B','A')     NOT NULL            COMMENT '等级配分',
  completeTime         int                       DEFAULT 0           COMMENT '完成时间',
  status               enum('2','-1','0','1')    DEFAULT '1'         COMMENT '-1 删除 0 禁用 1 未完成 2 完成'
);


-- 任务状态表
create table d_task_data
(
  id                   int                       auto_increment primary key,
  tId                  int                       not null            comment '任务Id',
  pId                  int                       not null            comment '流程Id，d_process_vice.id',
  currentLevel         tinyint                   not null            comment '当前流程走到哪一步',
  nextLevel            tinyint                   not null            comment '流程下一步',
  tDate                mediumint                 not null            comment '任务流程的月份，格式：201711',
  completeSituation    varchar(500)                                  comment '完成情况',
  problemSuggestions   varchar(500)                                  comment '问题和建议',
  analysis             varchar(500)                                  comment '问题分析',
  status               boolean                   default 1           comment '所属月份该流程是否关闭，0关闭，1进行中'
);

