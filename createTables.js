const { Sequelize } = require('sequelize');
const fs = require('fs');
const path = require('path');

// 🔑 Configure ta base ici
const sequelize = new Sequelize('chm', 'root', 'En11dh34&*05', {
  host: 'localhost',
  dialect: 'mysql'
});

const entities = {};

// Import automatique de tous les fichiers du dossier 'entity'
fs.readdirSync(path.join(__dirname, 'entity')).forEach(file => {
  if (file.endsWith('.js')) {
    const entity = require(`./entity/${file}`)(sequelize);
    entities[entity.name] = entity;
  }
});

// Si tu as des relations entre tables, tu peux les définir ici
// ex : entities.Order.belongsTo(entities.User);

sequelize.sync({ force: true }) // force:true recrée toutes les tables
  .then(() => {
    console.log('Toutes les tables ont été créées !');
    process.exit();
  })
  .catch(err => console.error(err));
