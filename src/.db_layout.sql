CREATE TABLE bans (
  id INTEGER PRIMARY KEY AUTOINCREMENT,  
  ip TEXT,
  reason TEXT,
  time INTEGER
);

CREATE TABLE cat (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT DEFAULT '',
  sort INTEGER DEFAULT 0,
  available INTEGER,  
  list TEXT
);

CREATE TABLE comments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  item INTEGER,
  title TEXT,
  comment TEXT,
  name TEXT,
  user INTEGER,
  date INTEGER,
  mail TEXT,
  ip TEXT
);

CREATE TABLE config (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT,
  page TEXT,
  title INTEGER,
  mail TEXT,
  rate INTEGER,
  comments INTEGER,
  commentssmall INTEGER,
  numcomments INTEGER,
  mincomments INTEGER,
  numtopuser INTEGER,
  picquali INTEGER,
  predownload INTEGER,
  writtenby INTEGER,
  menubreak INTEGER,
  vertical INTEGER,
  menu_width INTEGER,
  menu_height INTEGER,
  page_limit INTEGER,
  list_rows INTEGER,
  visitors_day INTEGER,
  visitors INTEGER,
  visitors_today INTEGER,
  visitors_yesterday INTEGER,
  visitors_increment INTEGER,
  visitors_lifetime INTEGER,
  register_activated INTEGER,
  password_recovery_activated INTEGER,
  guestbook_activated INTEGER,
  editor INTEGER,
  safemail INTEGER,
  topusers INTEGER,
  speciallinks INTEGER,
  latest_comments_days INTEGER,
  latest_comments_chars INTEGER,
  language TEXT,
  allow_compress INTEGER,
  menu_mode INTEGER,
  search_list TEXT,
  visitors_password TEXT,
  smileys INTEGER
);

CREATE TABLE dynamic (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  searcher TEXT,
  replacer TEXT,
  makebr INTEGER
);

CREATE TABLE item (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  cat INTEGER,
  subcat INTEGER,
  name TEXT,
  typ INTEGER,
  special INTEGER,
  showuser INTEGER,
  rate INTEGER,
  rating INTEGER,
  numratings INTEGER,
  comments INTEGER,
  description TEXT,
  content TEXT,
  image TEXT,
  sort INTEGER,
  user INTEGER,
  time INTEGER,
  time_changed INTEGER,
  link TEXT,
  available INTEGER,
  visible INTEGER
);

CREATE TABLE menu (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT,
  sort INTEGER,
  typ INTEGER,
  cat INTEGER,
  subcat INTEGER,
  item INTEGER,
  usertyp INTEGER,
  plugin TEXT,
  extern TEXT,
  visible INTEGER DEFAULT 1,
  popup INTEGER
);

CREATE TABLE poll (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  question TEXT,
  sort INTEGER,
  available INTEGER,
  answer1 TEXT,
  answers1 INTEGER,
  answer2 TEXT,
  answers2 INTEGER,
  answer3 TEXT,
  answers3 INTEGER,
  answer4 TEXT,
  answers4 INTEGER,
  answer5 TEXT,
  answers5 INTEGER,
  answer6 TEXT,
  answers6 INTEGER,
  answer7 TEXT,
  answers7 INTEGER,
  answer8 TEXT,
  answers8 INTEGER,
  answer9 TEXT,
  answers9 INTEGER,
  answer10 TEXT,
  answers10 INTEGER
);

CREATE TABLE subcat (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  cat INTEGER DEFAULT 0,
  name TEXT DEFAULT '',
  description TEXT,
  image TEXT,
  sort INTEGER DEFAULT 0,
  available INTEGER,
  jump INTEGER,
  list TEXT
);

CREATE TABLE user (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT,
  password TEXT,
  mail TEXT,
  showmail INTEGER,
  website TEXT,
  signatur TEXT,
  typ INTEGER,
  image TEXT,
  register INTEGER,
  registerip TEXT,
  login INTEGER,
  bday INTEGER,
  top INTEGER,
  points INTEGER DEFAULT 0,
  mail_guestbook INTEGER,
  mail_comments INTEGER,
  mail_register INTEGER,
  active INTEGER
);

CREATE TABLE visitors (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ip TEXT
);

CREATE TABLE visitors_counter (
  id TEXT PRIMARY KEY,
  browser TEXT,
  user INTEGER,
  typ INTEGER,
  content INTEGER,
  time INTEGER
);
