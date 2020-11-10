// @flow
export type ScheduleType = 'weekly' | 'fixed';
export type Weekday = 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday';

export type FixedScheduleEntry = {|
    end?: ?Date,
    start?: ?Date,
    type: 'fixed',
|};

export type WeeklyScheduleEntry = {|
    days?: ?Array<Weekday>,
    end?: ?Date,
    start?: ?Date,
    type: 'weekly',
|};

export type ScheduleEntry = FixedScheduleEntry | WeeklyScheduleEntry;
