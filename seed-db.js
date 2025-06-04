const mongoose = require('mongoose');
const dotenv = require('dotenv');
const fs = require('fs');
const path = require('path');
const bcrypt = require('bcryptjs');

// Load env vars
dotenv.config({ path: './config/config.env' });

// Load models
const User = require('./models/user');
const Video = require('./models/video');
const UserProgress = require('./models/userProgress');

// Connect to DB
mongoose.connect(process.env.MONGO_URI, {
  useNewUrlParser: true,
  useUnifiedTopology: true,
  useCreateIndex: true,
  useFindAndModify: false
});

// Read JSON files
const users = JSON.parse(
  fs.readFileSync(path.join(__dirname, '_data', 'users.json'), 'utf-8')
);
const videos = JSON.parse(
  fs.readFileSync(path.join(__dirname, '_data', 'videos.json'), 'utf-8')
);
const progress = JSON.parse(
  fs.readFileSync(path.join(__dirname, '_data', 'userProgress.json'), 'utf-8')
);

// Hash passwords before importing users
const hashPasswords = async () => {
  return Promise.all(users.map(async user => {
    const salt = await bcrypt.genSalt(10);
    user.password = await bcrypt.hash(user.password, salt);
    return user;
  }));
};

// Import into DB
const importData = async () => {
  try {
    const hashedUsers = await hashPasswords();
    
    await User.create(hashedUsers);
    await Video.create(videos);
    await UserProgress.create(progress);
    
    console.log('Data Imported...');
    process.exit();
  } catch (err) {
    console.error(err);
    process.exit(1);
  }
};

// Delete data
const deleteData = async () => {
  try {
    await User.deleteMany();
    await Video.deleteMany();
    await UserProgress.deleteMany();
    
    console.log('Data Destroyed...');
    process.exit();
  } catch (err) {
    console.error(err);
    process.exit(1);
  }
};

// Execute based on command line argument
if (process.argv[2] === '-i') {
  importData();
} else if (process.argv[2] === '-d') {
  deleteData();
} else {
  console.log('Please use -i to import or -d to delete data');
  process.exit();
}
