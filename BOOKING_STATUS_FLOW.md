# Booking Status Flow

## Overview
The vehicle rental booking system now supports a complete status flow from initial booking to completion.

## Status Flow Diagram
```
pending → confirmed → active → ongoing → returned → completed
                ↓
            cancelled (can be cancelled at pending or confirmed stage)
```

## Status Descriptions

### 1. **Pending**
- **Description**: Initial booking created, awaiting admin confirmation
- **Automatic**: Set when customer creates a new booking
- **Actions Available**: Admin can confirm or cancel
- **Vehicle Status**: Not affected

### 2. **Confirmed**
- **Description**: Booking confirmed by admin, reservation secured
- **Manual/Auto**: Admin manually confirms or auto-confirmed via payment
- **Actions Available**: Can still be cancelled before start date
- **Vehicle Status**: Reserved for this booking

### 3. **Active**
- **Description**: Start date has arrived, vehicle is ready for pickup
- **Automatic**: System automatically changes from "confirmed" to "active" when start date arrives
- **Actions Available**: Admin can mark as "ongoing" when customer picks up vehicle
- **Vehicle Status**: Reserved, awaiting pickup

### 4. **Ongoing**
- **Description**: Vehicle has been picked up and is currently in use
- **Manual**: Admin marks as "ongoing" when customer picks up the vehicle
- **Actions Available**: Admin can mark as "returned" when customer returns vehicle
- **Vehicle Status**: Rented (not available for other bookings)

### 5. **Returned**
- **Description**: Vehicle has been returned by customer
- **Manual**: Admin marks as "returned" after inspecting returned vehicle
- **Actions Available**: Admin can mark as "completed" after final processing
- **Vehicle Status**: Available for new bookings

### 6. **Completed**
- **Description**: Booking fully processed and closed
- **Manual**: Admin marks as "completed" after all paperwork is done
- **Actions Available**: Read-only, no further changes
- **Vehicle Status**: Available

### 7. **Cancelled**
- **Description**: Booking was cancelled (can happen from pending or confirmed)
- **Manual**: Admin or customer cancels the booking
- **Actions Available**: None (final status)
- **Vehicle Status**: Available

## Automatic Status Updates

The system automatically updates statuses in the following scenarios:

1. **Confirmed → Active**: When the start date arrives (checked on page load)
2. All other transitions require manual admin action to ensure proper verification

## Admin Actions

### Marking as Ongoing
When a customer arrives to pick up the vehicle:
1. Verify customer identity and license
2. Inspect vehicle condition and take photos
3. Mark booking as "ongoing" in the system
4. Hand over vehicle keys

### Marking as Returned
When a customer returns the vehicle:
1. Inspect vehicle for damage
2. Check fuel level
3. Mark booking as "returned" in the system
4. Process any additional charges if needed

### Marking as Completed
After all paperwork is done:
1. Finalize payment records
2. Update vehicle maintenance log if needed
3. Mark booking as "completed"
4. Archive booking record

## Technical Implementation

### Database Schema
```sql
ALTER TABLE bookings MODIFY COLUMN status 
ENUM('pending', 'confirmed', 'active', 'ongoing', 'returned', 'completed', 'cancelled') 
NOT NULL DEFAULT 'pending';
```

### Backend Logic
- `Booking::updateBookingStatuses()` - Automatically updates confirmed bookings to active
- Status transitions are validated to ensure proper flow
- Vehicle availability is checked based on active/ongoing bookings

### Frontend Display
- Color-coded status badges for easy visual identification
- Filter dropdown includes all status options
- Edit form allows manual status transitions by admin

## Color Coding

- **Pending**: Yellow (⚠️ Awaiting action)
- **Confirmed**: Green (✅ Reservation secured)
- **Active**: Purple (🟣 Ready for pickup)
- **Ongoing**: Blue (🔵 Vehicle in use)
- **Returned**: Indigo (🟪 Vehicle returned)
- **Completed**: Dark Green (✅ Fully processed)
- **Cancelled**: Red (❌ Booking cancelled)
