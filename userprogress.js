const mongoose = require('mongoose');

const VideoProgressSchema = new mongoose.Schema({
  video: {
    type: mongoose.Schema.ObjectId,
    ref: 'Video',
    required: true
  },
  progress: {
    type: Number,
    default: 0,
    min: 0,
    max: 100
  },
  completed: {
    type: Boolean,
    default: false
  },
  lastWatched: {
    type: Date,
    default: Date.now
  },
  notes: {
    type: String,
    maxlength: [1000, 'Notes cannot be more than 1000 characters']
  },
  bookmarked: {
    type: Boolean,
    default: false
  }
});

const UserProgressSchema = new mongoose.Schema({
  user: {
    type: mongoose.Schema.ObjectId,
    ref: 'User',
    required: true,
    unique: true
  },
  videos: [VideoProgressSchema],
  totalVideosWatched: {
    type: Number,
    default: 0
  },
  totalCompletionPercentage: {
    type: Number,
    default: 0
  },
  lastActivity: {
    type: Date,
    default: Date.now
  },
  streakDays: {
    type: Number,
    default: 0
  },
  achievements: [{
    type: String,
    enum: [
      'first_video',
      'five_videos',
      'ten_videos',
      'daily_streak_3',
      'daily_streak_7',
      'daily_streak_30',
      'completed_course'
    ]
  }]
}, {
  timestamps: true,
  toJSON: { virtuals: true },
  toObject: { virtuals: true }
});

// Calculate totals before saving
UserProgressSchema.pre('save', function(next) {
  if (this.videos && this.isModified('videos')) {
    this.totalVideosWatched = this.videos.filter(v => v.completed).length;
    
    const totalProgress = this.videos.reduce(
      (sum, video) => sum + video.progress,
      0
    );
    this.totalCompletionPercentage = this.videos.length > 0 
      ? Math.round(totalProgress / this.videos.length)
      : 0;
  }

  // Update last activity
  this.lastActivity = new Date();
  next();
});

// Update streak logic
UserProgressSchema.methods.updateStreak = function() {
  const now = new Date();
  const lastActivity = new Date(this.lastActivity);
  
  // Reset streak if more than 2 days have passed
  if ((now - lastActivity) > 2 * 24 * 60 * 60 * 1000) {
    this.streakDays = 1;
  } 
  // Increment streak if activity was yesterday
  else if (
    now.getDate() === lastActivity.getDate() + 1 &&
    now.getMonth() === lastActivity.getMonth() &&
    now.getFullYear() === lastActivity.getFullYear()
  ) {
    this.streakDays += 1;
  }
  // Same day - don't increment
  else if (
    now.getDate() === lastActivity.getDate() &&
    now.getMonth() === lastActivity.getMonth() &&
    now.getFullYear() === lastActivity.getFullYear()
  ) {
    // Do nothing
  }
  // Otherwise reset streak
  else {
    this.streakDays = 1;
  }

  // Check for streak achievements
  if (this.streakDays >= 3 && !this.achievements.includes('daily_streak_3')) {
    this.achievements.push('daily_streak_3');
  }
  if (this.streakDays >= 7 && !this.achievements.includes('daily_streak_7')) {
    this.achievements.push('daily_streak_7');
  }
  if (this.streakDays >= 30 && !this.achievements.includes('daily_streak_30')) {
    this.achievements.push('daily_streak_30');
  }
};

// Check for video count achievements
UserProgressSchema.methods.checkVideoAchievements = function() {
  const completedCount = this.videos.filter(v => v.completed).length;
  
  if (completedCount >= 1 && !this.achievements.includes('first_video')) {
    this.achievements.push('first_video');
  }
  if (completedCount >= 5 && !this.achievements.includes('five_videos')) {
    this.achievements.push('five_videos');
  }
  if (completedCount >= 10 && !this.achievements.includes('ten_videos')) {
    this.achievements.push('ten_videos');
  }
};

module.exports = mongoose.model('UserProgress', UserProgressSchema);
