-- 流程主表
create table d_process
(
  id                   int                       auto_increment primary key,
  name                 varchar(200)              not null            comment '流程名称',
  deptNo               varchar(8)                not null            comment '所属部门Id',
  level                tinyint                   not null            comment '流程的步骤数量',
  creator              varchar(10)               not null            comment '流程创建者',
  lastModifier         varchar(10)               not null            comment '流程创建者',
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
  pDescribe            varchar(200)              not null            comment '流程步骤描述',
  commitAll            boolean                   not null            comment '是否需要全部提交',
  deptNos              varchar(1000)             not null            comment '参与到该步骤的部门',
  empNos               varchar(1000)             not null            comment '参与到该步骤的人员',
  notInIds             varchar(1000)             not null            comment '该步骤排除的人员'
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
  pId                  int                       not null            comment '该任务应用需要执行的流程的Id',
  typeId               int                       not null            comment '任务分类Id',
  serialNumber         int                       DEFAULT 0           COMMENT '序号',
  content              varchar(500)              NOT NULL            COMMENT '任务内容',
  duty                 varchar(300)                                  comment '部门责任',
  firstLevel           int                       not null            comment '所属一级目标任务',
  secondLevel          int                       not null            comment '所属二级目标任务',
  thirdLevel           int                       not null            comment '所属三级目标任务',
  releaseTime          int                       NOT NULL            COMMENT '发布时间，时间戳',
  timeLimit            mediumint                 NOT NULL            COMMENT '时限',
  level                enum('D','C','B','A')     NOT NULL            COMMENT '等级配分',
  completeTime         int                       DEFAULT 0           COMMENT '完成时间',
  status               enum('2','-1','0','1','3')DEFAULT '1'         COMMENT '-1 删除 0 禁用 1 未完成 2 进行中标记完成 3 最终完成'
);


-- 任务状态表
create table d_task_data
(
  id                   int                       auto_increment primary key,
  tId                  int                       not null            comment '任务Id',
  pId                  int                       not null            comment '流程Id，d_process_vice.id',
  deptNo               varchar(10)               not null            comment '部门Id'
  currentLevel         tinyint                   not null            comment '当前流程走到哪一步',
  nextLevel            tinyint                   not null            comment '流程下一步',
  tDate                mediumint                 not null            comment '任务流程的月份，格式：201711',
  completeSituation    varchar(500)                                  comment '完成情况',
  problemSuggestions   varchar(500)                                  comment '问题和建议',
  analysis             varchar(500)                                  comment '问题分析',
  status               boolean                   default 1           comment '所属月份该流程是否关闭，0关闭，1进行中'
);


-- 督办记录表
create table d_supervise_record
(
  id                   int                       auto_increment primary key,
  srUser               varchar(10)               not null            comment '督办人Id',
  tId                  int                       not null            comment '任务Id',
  srDate               mediumint                 not null            comment '督办任务的月份',
  srTime               int                       not null            comment '发起督办时间'
);


-- 任务列表查看权限表(能查看所有任务列表的人员记录)
create table d_tasklist_authority
(
  id                   int                       auto_increment primary key,
  type                 enum('dept','person','tag')       not null,
  value                varchar(10)               not null
);


-- 一级目标任务
create table d_task_level_first
(
  id                   int                       auto_increment primary key,
  serialNum            int                       not null            comment '序号',
  leader               varchar(10)               not null            comment '牵头领导',
  title                varchar(300)              not null            comment '任务标题',
  detail               varchar(500)              not null            comment '详细说明'
);


-- 二级目标任务
create table d_task_level_second
(
  id                   int                       auto_increment primary key,
  serialNum            int                       not null            comment '序号',
  leader               varchar(10)               not null            comment '责任领导',
  title                varchar(300)              not null            comment '任务标题',
  detail               varchar(500)              not null            comment '详细说明',
  deptNo               varchar(80)               not null            comment '责任部室',
  prevLevel            int                       not null            comment '上一级'
);


-- 三级目标任务
create table d_task_level_third
(
  id                   int                       auto_increment primary key,
  serialNum            int                       not null            comment '序号',
  detail               varchar(300)              not null            comment '任务举措',
  duty                 varchar(300)              not null            comment '部门责任',
  leader               varchar(10)               not null            comment '责任领导',
  prevLevel            int                       not null            comment '上一级'
);


-- 相关部门名称对应表
create table d_relevant_departments
(
  id                   int                       auto_increment primary key,
  relevantName         varchar(100)              not null,
  deptNo               varchar(10)               not null,
  deptName             varchar(100)              not null,
  compNo               tinyint                   not null
);
