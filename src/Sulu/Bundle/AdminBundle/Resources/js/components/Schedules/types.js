// @flow
export type ScheduleType = 'weekly' | 'fixed';
export type Weekday = 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday';

export type FixedScheduleEntry = {|
    end?: ?string,
    start?: ?string,
    type: 'fixed',
|};

export type WeeklyScheduleEntry = {|
    days?: ?Array<Weekday>,
    end?: ?string,
    start?: ?string,
    type: 'weekly',
|};

export type ScheduleEntry = FixedScheduleEntry | WeeklyScheduleEntry;
