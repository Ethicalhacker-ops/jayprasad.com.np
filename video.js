const mongoose = require('mongoose');
const slugify = require('slugify');

const VideoSchema = new mongoose.Schema({
  title: {
    type: String,
    required: [true, 'Please add a title'],
    trim: true,
    maxlength: [100, 'Title cannot be more than 100 characters']
  },
  slug: String,
  description: {
    type: String,
    required: [true, 'Please add a description'],
    maxlength: [500, 'Description cannot be more than 500 characters']
  },
  url: {
    type: String,
    required: [true, 'Please add a video URL'],
    match: [
      /https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)/,
      'Please use a valid URL with HTTP or HTTPS'
    ]
  },
  thumbnail: {
    type: String,
    default: 'default-thumbnail.jpg'
  },
  duration: {
    type: Number,
    required: [true, 'Please add video duration in seconds']
  },
  category: {
    type: String,
    required: [true, 'Please add a category'],
    enum: [
      'programming',
      'design',
      'business',
      'marketing',
      'photography',
      'music',
      'other'
    ]
  },
  level: {
    type: String,
    required: [true, 'Please add a difficulty level'],
    enum: ['beginner', 'intermediate', 'advanced']
  },
  tags: {
    type: [String],
    required: true,
    validate: {
      validator: function(v) {
        return v.length > 0;
      },
      message: 'Please add at least one tag'
    }
  },
  isFree: {
    type: Boolean,
    default: true
  },
  averageRating: {
    type: Number,
    min: [1, 'Rating must be at least 1'],
    max: [5, 'Rating must can not be more than 5']
  },
  ratingsQuantity: {
    type: Number,
    default: 0
  },
  createdAt: {
    type: Date,
    default: Date.now
  },
  updatedAt: {
    type: Date,
    default: Date.now
  },
  createdBy: {
    type: mongoose.Schema.ObjectId,
    ref: 'User',
    required: true
  }
}, {
  toJSON: { virtuals: true },
  toObject: { virtuals: true }
});

// Create video slug from the title
VideoSchema.pre('save', function(next) {
  this.slug = slugify(this.title, { lower: true });
  next();
});

// Cascade delete user progress when video is deleted
VideoSchema.pre('remove', async function(next) {
  await this.model('UserProgress').updateMany(
    { 'videos.video': this._id },
    { $pull: { videos: { video: this._id } } }
  );
  next();
});

// Reverse populate with virtuals
VideoSchema.virtual('progress', {
  ref: 'UserProgress',
  localField: '_id',
  foreignField: 'videos.video',
  count: true
});

// Static method to get average rating and save
VideoSchema.statics.getAverageRating = async function(videoId) {
  const obj = await this.aggregate([
    {
      $match: { _id: videoId }
    },
    {
      $lookup: {
        from: 'reviews',
        localField: '_id',
        foreignField: 'video',
        as: 'reviews'
      }
    },
    {
      $addFields: {
        averageRating: { $avg: '$reviews.rating' },
        ratingsQuantity: { $size: '$reviews' }
      }
    },
    {
      $project: {
        averageRating: 1,
        ratingsQuantity: 1
      }
    }
  ]);

  try {
    await this.findByIdAndUpdate(videoId, {
      averageRating: obj[0]?.averageRating || 0,
      ratingsQuantity: obj[0]?.ratingsQuantity || 0
    });
  } catch (err) {
    console.error(err);
  }
};

module.exports = mongoose.model('Video', VideoSchema);
