const request = require('supertest');
const mongoose = require('mongoose');
const app = require('./server');
const User = require('./models/user');
const Video = require('./models/video');
const UserProgress = require('./models/userProgress');

let authToken;
let testUserId;
let testVideoId;

// Test data
const testUser = {
  name: 'Test User',
  email: 'test@example.com',
  password: 'Test1234',
  role: 'user'
};

const testVideo = {
  title: 'Test Video',
  description: 'This is a test video',
  url: 'https://example.com/video.mp4',
  duration: 300,
  category: 'programming',
  level: 'beginner',
  tags: ['javascript', 'nodejs']
};

beforeAll(async () => {
  // Connect to test database
  await mongoose.connect(process.env.MONGO_URI_TEST, {
    useNewUrlParser: true,
    useUnifiedTopology: true,
    useCreateIndex: true,
    useFindAndModify: false
  });

  // Clear test database
  await User.deleteMany();
  await Video.deleteMany();
  await UserProgress.deleteMany();

  // Create test user
  const user = await User.create(testUser);
  testUserId = user._id;

  // Create test video
  const video = await Video.create({
    ...testVideo,
    createdBy: testUserId
  });
  testVideoId = video._id;

  // Login to get token
  const res = await request(app)
    .post('/api/v1/auth/login')
    .send({
      email: testUser.email,
      password: testUser.password
    });
  
  authToken = res.body.token;
});

afterAll(async () => {
  // Close database connection
  await mongoose.connection.close();
});

describe('Authentication', () => {
  test('should register a new user', async () => {
    const res = await request(app)
      .post('/api/v1/auth/register')
      .send({
        name: 'New User',
        email: 'new@example.com',
        password: 'Test1234',
        role: 'user'
      });
    
    expect(res.statusCode).toEqual(200);
    expect(res.body).toHaveProperty('token');
  });

  test('should login existing user', async () => {
    const res = await request(app)
      .post('/api/v1/auth/login')
      .send({
        email: testUser.email,
        password: testUser.password
      });
    
    expect(res.statusCode).toEqual(200);
    expect(res.body).toHaveProperty('token');
  });

  test('should not login with invalid credentials', async () => {
    const res = await request(app)
      .post('/api/v1/auth/login')
      .send({
        email: testUser.email,
        password: 'wrongpassword'
      });
    
    expect(res.statusCode).toEqual(401);
  });
});

describe('Video Routes', () => {
  test('should get all videos', async () => {
    const res = await request(app)
      .get('/api/v1/videos')
      .set('Authorization', `Bearer ${authToken}`);
    
    expect(res.statusCode).toEqual(200);
    expect(Array.isArray(res.body.data)).toBeTruthy();
  });

  test('should get single video', async () => {
    const res = await request(app)
      .get(`/api/v1/videos/${testVideoId}`)
      .set('Authorization', `Bearer ${authToken}`);
    
    expect(res.statusCode).toEqual(200);
    expect(res.body.data).toHaveProperty('title', testVideo.title);
  });
});

describe('User Progress Routes', () => {
  test('should update user progress', async () => {
    const res = await request(app)
      .post('/api/v1/progress')
      .set('Authorization', `Bearer ${authToken}`)
      .send({
        videoId: testVideoId,
        progress: 50,
        completed: false
      });
    
    expect(res.statusCode).toEqual(200);
    expect(res.body.data).toHaveProperty('videos');
  });

  test('should get user progress', async () => {
    const res = await request(app)
      .get('/api/v1/progress')
      .set('Authorization', `Bearer ${authToken}`);
    
    expect(res.statusCode).toEqual(200);
    expect(res.body.data).toHaveProperty('videos');
  });
});
