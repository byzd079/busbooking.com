# Bus Community & Behavior Score System - Comprehensive Plan

## Overview
A community-driven feature allowing passengers to share bus experiences through images, comments, and behavior scores. Each bus listing will have a dedicated gallery corner where users can post photos, captions, and rate bus operator behavior.

## Feature Components

### 1. Bus Image Gallery & Posts System
**Purpose:** Allow passengers to share real bus images and experiences

**Key Features:**
- Upload bus exterior/interior images with captions
- Upload seat condition images
- View all posts for a specific bus
- Comment threads on each post
- Like/helpful reactions
- Sort by: Most Recent, Most Helpful, Highest Rated

**UI/UX Considerations:**
- **Gallery Corner Position:** Top-right card on bus detail page (non-intrusive)
- **Mobile-First:** Card-based layout, infinite scroll on mobile
- **Image Optimization:** Compress uploads to 800px max width, WebP format
- **Empty State:** "Be the first to share a photo of this bus!" with CTA button
- **Loading State:** Skeleton screens during image load

### 2. Bus Behavior Score System
**Purpose:** Crowd-sourced accountability for bus operators

**Scoring Dimensions:**
1. **Route Adherence** (1-5 stars): Does bus follow published route?
2. **Punctuality** (1-5 stars): Picks up passengers on time?
3. **Cleanliness** (1-5 stars): Bus interior condition
4. **Driver Behavior** (1-5 stars): Professional, safe driving
5. **Overall Experience** (1-5 stars): Would you recommend?

**Scoring Rules:**
- Only passengers with completed trips can score (verified via order history)
- One score per trip per user
- Scores visible after 5+ ratings (prevent manipulation)
- Rolling 90-day average (recent experience weights more)

**Display:**
- Aggregate score badge on bus card in search results
- Detailed breakdown on bus detail page
- Trend indicator (↑ improving, ↓ declining, → stable)

### 3. User Panel Integration
**Purpose:** Show passengers their contribution and bus quality trends

**Dashboard Sections:**
- **My Posts:** Gallery of user's uploaded images
- **My Scores:** List of behavior scores submitted
- **Helpful Contributions:** Posts/comments marked helpful by others
- **Watchlist:** Save favorite buses, get alerts on score changes

---

## Database Schema

### Table: `bus_posts`
```sql
CREATE TABLE bus_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bus_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NULL, -- Link to trip for verification
    post_type ENUM('bus_exterior', 'bus_interior', 'seat', 'amenity', 'other') NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    caption TEXT NULL,
    helpful_count INT UNSIGNED DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE, -- Admin can verify authentic posts
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (bus_id) REFERENCES buslists(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    
    INDEX idx_bus_posts_bus_id (bus_id),
    INDEX idx_bus_posts_user_id (user_id),
    INDEX idx_bus_posts_created_at (created_at)
);
```

### Table: `post_comments`
```sql
CREATE TABLE post_comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    parent_comment_id BIGINT UNSIGNED NULL, -- For nested replies
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id) REFERENCES bus_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES post_comments(id) ON DELETE CASCADE,
    
    INDEX idx_post_comments_post_id (post_id),
    INDEX idx_post_comments_user_id (user_id)
);
```

### Table: `bus_behavior_scores`
```sql
CREATE TABLE bus_behavior_scores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bus_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL, -- Must link to completed trip
    route_adherence_score TINYINT UNSIGNED NOT NULL CHECK (route_adherence_score BETWEEN 1 AND 5),
    punctuality_score TINYINT UNSIGNED NOT NULL CHECK (punctuality_score BETWEEN 1 AND 5),
    cleanliness_score TINYINT UNSIGNED NOT NULL CHECK (cleanliness_score BETWEEN 1 AND 5),
    driver_behavior_score TINYINT UNSIGNED NOT NULL CHECK (driver_behavior_score BETWEEN 1 AND 5),
    overall_score TINYINT UNSIGNED NOT NULL CHECK (overall_score BETWEEN 1 AND 5),
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (bus_id) REFERENCES buslists(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_order_score (user_id, order_id), -- One score per trip
    INDEX idx_behavior_scores_bus_id (bus_id),
    INDEX idx_behavior_scores_created_at (created_at)
);
```

### Table: `post_helpful_marks` (Optional - for "helpful" feature)
```sql
CREATE TABLE post_helpful_marks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id) REFERENCES bus_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_post_helpful (user_id, post_id)
);
```

---

## Models & Relationships

### BusPost Model
```php
class BusPost extends Model
{
    protected $fillable = [
        'bus_id', 'user_id', 'order_id', 'post_type', 
        'image_path', 'caption', 'helpful_count', 'is_verified'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'helpful_count' => 'integer'
    ];

    // Relationships
    public function buslist() {
        return $this->belongsTo(buslist::class, 'bus_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function comments() {
        return $this->hasMany(PostComment::class, 'post_id');
    }

    public function helpfulMarks() {
        return $this->hasMany(PostHelpfulMark::class, 'post_id');
    }

    // Helper Methods
    public function isHelpfulBy($userId) {
        return $this->helpfulMarks()->where('user_id', $userId)->exists();
    }

    public function getImageUrlAttribute() {
        return asset('storage/bus_posts/' . $this->image_path);
    }
}
```

### PostComment Model
```php
class PostComment extends Model
{
    protected $fillable = [
        'post_id', 'user_id', 'parent_comment_id', 'comment_text'
    ];

    public function post() {
        return $this->belongsTo(BusPost::class, 'post_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function parent() {
        return $this->belongsTo(PostComment::class, 'parent_comment_id');
    }

    public function replies() {
        return $this->hasMany(PostComment::class, 'parent_comment_id');
    }
}
```

### BusBehaviorScore Model
```php
class BusBehaviorScore extends Model
{
    protected $fillable = [
        'bus_id', 'user_id', 'order_id',
        'route_adherence_score', 'punctuality_score', 
        'cleanliness_score', 'driver_behavior_score', 
        'overall_score', 'comment'
    ];

    protected $casts = [
        'route_adherence_score' => 'integer',
        'punctuality_score' => 'integer',
        'cleanliness_score' => 'integer',
        'driver_behavior_score' => 'integer',
        'overall_score' => 'integer'
    ];

    public function buslist() {
        return $this->belongsTo(buslist::class, 'bus_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function order() {
        return $this->belongsTo(Order::class);
    }

    // Static method to get aggregate behavior scores
    public static function getAggregateBehaviorScore($busId) {
        $scores = self::where('bus_id', $busId)
            ->where('created_at', '>=', now()->subDays(90)) // Last 90 days
            ->get();

        if ($scores->isEmpty()) {
            return null;
        }

        return [
            'route_adherence' => round($scores->avg('route_adherence_score'), 1),
            'punctuality' => round($scores->avg('punctuality_score'), 1),
            'cleanliness' => round($scores->avg('cleanliness_score'), 1),
            'driver_behavior' => round($scores->avg('driver_behavior_score'), 1),
            'overall' => round($scores->avg('overall_score'), 1),
            'total_scores' => $scores->count()
        ];
    }
}
```

---

## Controllers

### BusPostController
**Responsibilities:**
- Upload and store bus images
- List posts for a bus
- Mark posts as helpful
- Delete own posts (soft delete)

**Key Methods:**
```php
- index($busId): Display all posts for a bus
- create(): Show upload form
- store(Request $request): Handle image upload
- toggleHelpful($postId): Mark/unmark as helpful
- destroy($postId): Soft delete post
```

**Validation Rules:**
```php
'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120', // 5MB max
'caption' => 'nullable|string|max:500',
'post_type' => 'required|in:bus_exterior,bus_interior,seat,amenity,other',
'bus_id' => 'required|exists:buslists,id'
```

**Image Processing:**
- Resize to max 800px width (maintain aspect ratio)
- Convert to WebP for better compression
- Store in `storage/app/public/bus_posts/`
- Generate unique filename: `bus_{bus_id}_{timestamp}_{random}.webp`

### PostCommentController
**Responsibilities:**
- Add comments to posts
- Edit own comments (within 15 minutes)
- Delete own comments

**Key Methods:**
```php
- store(Request $request, $postId): Add comment
- update(Request $request, $commentId): Edit comment
- destroy($commentId): Delete comment
```

### BehaviorScoreController
**Responsibilities:**
- Show behavior score form (only for completed trips)
- Submit behavior scores
- Display aggregate behavior scores

**Key Methods:**
```php
- showForm($orderId): Show score form for completed trip
- store(Request $request): Submit behavior scores
- getBusBehaviorSummary($busId): Get aggregate scores for display
```

**Validation:**
- User must have completed trip (order status = 'completed')
- User can only score each trip once
- All score fields required (1-5 range)

---

## Routes Structure

```php
// Bus Posts Routes (Auth Required)
Route::middleware(['auth'])->group(function () {
    Route::get('/bus/{busId}/gallery', [BusPostController::class, 'index'])->name('bus.gallery');
    Route::post('/bus-post', [BusPostController::class, 'store'])->name('bus.post.store');
    Route::post('/bus-post/{postId}/helpful', [BusPostController::class, 'toggleHelpful'])->name('bus.post.helpful');
    Route::delete('/bus-post/{postId}', [BusPostController::class, 'destroy'])->name('bus.post.destroy');
    
    // Post Comments
    Route::post('/post/{postId}/comment', [PostCommentController::class, 'store'])->name('post.comment.store');
    Route::put('/comment/{commentId}', [PostCommentController::class, 'update'])->name('post.comment.update');
    Route::delete('/comment/{commentId}', [PostCommentController::class, 'destroy'])->name('post.comment.destroy');
    
    // Behavior Scores
    Route::get('/order/{orderId}/behavior-score', [BehaviorScoreController::class, 'showForm'])->name('behavior.score.form');
    Route::post('/behavior-score', [BehaviorScoreController::class, 'store'])->name('behavior.score.store');
});

// Public Routes (No Auth Required)
Route::get('/bus/{busId}/behavior-summary', [BehaviorScoreController::class, 'getBusBehaviorSummary'])->name('bus.behavior.summary');
```

---

## UI/UX Design Specifications

### 1. Bus Gallery Corner (Search Results Page)

**Position:** Small preview card in top-right of each bus row

```
┌─────────────────────────────────────────────┐
│ Bus Name | Route | Time | Price             │
│ ⭐ 4.2 (125 reviews)                        │
│                                             │
│ ┌───────────────┐  Seats: 30 available     │
│ │ 📷 Gallery    │                           │
│ │ 12 photos     │  [View Details] [Book]   │
│ └───────────────┘                           │
└─────────────────────────────────────────────┘
```

**Click Action:** Opens modal/drawer with full gallery

**Mobile:** Gallery preview shows as horizontal scrollable row below bus details

### 2. Bus Detail Page - Gallery Section

**Layout:** Tabbed interface
```
[ Details ] [ Gallery ] [ Reviews ] [ Behavior Score ]
```

**Gallery Tab:**
- Masonry grid layout (2 cols mobile, 3 cols tablet, 4 cols desktop)
- Each card shows: thumbnail, caption preview, author, date, helpful count
- Filter buttons: All | Exterior | Interior | Seats | Amenities
- Upload FAB button (bottom-right, primary color)

**Modal/Detail View:**
```
┌─────────────────────────────────────────────┐
│  [← Back]           Bus Image Gallery      │
├─────────────────────────────────────────────┤
│                                             │
│          [Large Image Display]              │
│                                             │
│  Posted by: John Doe | 2 days ago           │
│  Caption: Clean and comfortable seats!      │
│  👍 Helpful (23)                            │
│                                             │
│  💬 Comments (5)                            │
│  ┌───────────────────────────────────────┐ │
│  │ Alice: Great photo, thanks for sharing││ │
│  │ Bob: Is the AC working well?          ││ │
│  └───────────────────────────────────────┘ │
│  [Add Comment]                              │
└─────────────────────────────────────────────┘
```

### 3. Upload Flow

**Step 1:** Upload button opens modal
**Step 2:** Image selection (drag-drop or file picker)
**Step 3:** Crop/rotate tool (optional)
**Step 4:** Add caption and select category
**Step 5:** Submit with loading indicator
**Step 6:** Success toast + redirect to gallery

**Accessibility:**
- All images require alt text (auto-generated from caption)
- Keyboard navigation for gallery grid
- Screen reader announcements for helpful marks

### 4. Behavior Score Form

**Triggered After:** Trip completion (show banner: "Rate your experience with [Bus Name]")

**Form Layout:** Single-page, mobile-optimized
```
┌─────────────────────────────────────────────┐
│  How was your trip with [Bus Name]?         │
├─────────────────────────────────────────────┤
│  Route Adherence                            │
│  ☆☆☆☆☆ (tap to rate)                        │
│                                             │
│  Punctuality                                │
│  ☆☆☆☆☆                                      │
│                                             │
│  Cleanliness                                │
│  ☆☆☆☆☆                                      │
│                                             │
│  Driver Behavior                            │
│  ☆☆☆☆☆                                      │
│                                             │
│  Overall Experience                         │
│  ☆☆☆☆☆                                      │
│                                             │
│  [Optional: Add detailed comment]           │
│  ┌───────────────────────────────────────┐ │
│  │                                       │ │
│  └───────────────────────────────────────┘ │
│                                             │
│  [Skip for Now]        [Submit Rating]      │
└─────────────────────────────────────────────┘
```

**Star Interaction:**
- Tap to select rating (mobile)
- Hover to preview rating (desktop)
- Color transition: gray → yellow (1-3 stars) → green (4-5 stars)
- Haptic feedback on mobile

### 5. Behavior Score Display (Bus Detail Page)

**Aggregate Score Card:**
```
┌─────────────────────────────────────────────┐
│  Behavior Score                    4.2 ⭐   │
│  Based on 87 passenger ratings (90 days)    │
├─────────────────────────────────────────────┤
│  Route Adherence    ████████░░ 4.1          │
│  Punctuality        ███████░░░ 3.8 ↓        │
│  Cleanliness        █████████░ 4.5 ↑        │
│  Driver Behavior    █████████░ 4.6          │
│  Overall Experience ████████░░ 4.2          │
└─────────────────────────────────────────────┘
```

**Trend Indicators:**
- ↑ Improving (score increased >0.3 in last 30 days)
- ↓ Declining (score decreased >0.3 in last 30 days)
- → Stable (change <0.3)

**Color Coding:**
- 4.5-5.0: Green (Excellent)
- 3.5-4.4: Blue (Good)
- 2.5-3.4: Yellow (Fair)
- 1.0-2.4: Red (Needs Improvement)

### 6. User Panel Integration

**New Tab:** "My Contributions"

**Sections:**
1. **My Posts** (Gallery grid of user's uploads)
2. **My Behavior Scores** (List with bus name, date, overall score)
3. **Helpful Badges** (Gamification: Bronze/Silver/Gold based on helpful marks)

---

## Security Considerations

### 1. Image Upload Security
- Validate file type using MIME detection (not just extension)
- Scan for malicious content (use intervention/image package)
- Store outside public webroot, serve via controller with auth check
- Implement rate limiting: max 5 uploads per hour per user

### 2. Content Moderation
- Flag system for inappropriate images/comments
- Admin review queue for flagged content
- Auto-hide posts with 5+ flags until reviewed
- User suspension after 3 confirmed violations

### 3. Score Manipulation Prevention
- Link scores to verified completed trips (order_id required)
- One score per trip per user (database constraint)
- Hide aggregate scores until 5+ ratings exist
- Implement rolling average (90 days) to reduce impact of old scores

### 4. Privacy
- Option to upload anonymously (display name hidden)
- Users can delete their own posts/comments
- Admin cannot see which user marked post as "helpful" (privacy)

---

## Implementation Phases

### Phase 1: Database & Models (Week 1)
- Create migrations for 4 tables
- Build Eloquent models with relationships
- Seed test data
- Write model unit tests

### Phase 2: Gallery & Posts (Week 2)
- BusPostController with image upload
- Gallery display view (masonry grid)
- Upload modal/form
- Helpful marking feature
- Comment system (basic, no nesting yet)

### Phase 3: Behavior Scores (Week 3)
- BehaviorScoreController
- Score submission form
- Aggregate score calculation
- Display score card on bus detail page
- User panel integration

### Phase 4: Polish & Optimization (Week 4)
- Image optimization (WebP conversion, resizing)
- Lazy loading for gallery
- Infinite scroll
- Nested comment replies
- Admin moderation dashboard
- Mobile responsive refinements

---

## Testing Plan

### Unit Tests
- Model relationships work correctly
- Score aggregation calculations accurate
- Image upload validation rules
- One-score-per-trip constraint enforced

### Feature Tests
- User can upload image with caption
- User can only score completed trips
- Aggregate scores update correctly
- Helpful marking toggles properly
- Comments display in correct order

### Manual Testing Checklist
- [ ] Upload works on mobile (file picker + camera)
- [ ] Images display correctly across devices
- [ ] Score form accessible via keyboard
- [ ] Gallery loads fast with 50+ images
- [ ] Comment threads easy to follow
- [ ] Admin can moderate flagged content

---

## Future Enhancements (Post-MVP)

1. **Video Support:** Allow 15-second video posts (seats, AC demo, etc.)
2. **Image Tagging:** Tag users in photos (with approval)
3. **Leaderboard:** Top contributors with most helpful posts
4. **Bus Comparison:** Side-by-side behavior score comparison
5. **Push Notifications:** Alert users when their favorite buses get new posts
6. **AI Moderation:** Auto-detect inappropriate content using ML
7. **Export Reports:** Bus operators can download behavior score PDFs
8. **Real-time Updates:** Live updates using WebSockets when new posts added

---

## Metrics to Track

### Engagement Metrics
- Posts per day (target: 10+ daily after 3 months)
- Comments per post (target: avg 2+)
- Helpful marks per post (target: avg 5+)
- Behavior scores per trip (target: 30% completion rate)

### Quality Metrics
- Flagged content rate (target: <2%)
- Post deletion rate (target: <5%)
- User retention after first post (target: 60%+)

### Business Impact
- Correlation between high behavior scores and booking rate
- Reduction in customer support tickets re: bus quality
- Increase in repeat bookings for high-scored buses

---

## Dependencies & Libraries

### Laravel Packages
```bash
composer require intervention/image  # Image processing
composer require spatie/laravel-medialibrary  # Media management (optional)
```

### JavaScript Libraries
```javascript
// Masonry layout
npm install masonry-layout imagesloaded

// Image upload with preview
npm install filepond filepond-plugin-image-preview

// Infinite scroll
npm install infinite-scroll
```

### Storage Configuration
```env
FILESYSTEM_DISK=public
AWS_BUCKET=your-bucket  # For production (S3 recommended)
```

---

## Conclusion

This system creates a community-driven feedback loop that:
1. **Increases Trust:** Real passenger photos > stock images
2. **Improves Quality:** Bus operators see scores, improve service
3. **Boosts Engagement:** Users return to see responses to their posts
4. **Reduces Risk:** Passengers make informed decisions based on recent behavior scores

**Success Criteria:** 
- 500+ posts in first 3 months
- 40% of completed trips get behavior scores
- Bus operators actively respond to feedback
