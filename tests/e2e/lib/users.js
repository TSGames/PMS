// Zugangsdaten der Mock-Benutzer (siehe tests/mock/seed.sql)
const USERS = {
  admin: { name: 'admin', password: 'admin123', typ: 3, label: 'Super-Administrator' },
  redakteur: { name: 'redakteur', password: 'admin123', typ: 2, label: 'Administrator' },
  moderator: { name: 'moderator', password: 'admin123', typ: 1, label: 'Moderator' },
  gast: { name: 'gast', password: 'admin123', typ: 0, label: 'Benutzer' },
};

module.exports = { USERS };
