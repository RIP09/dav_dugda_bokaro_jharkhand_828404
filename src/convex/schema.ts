import { defineSchema, defineTable } from "convex/schema";
import { v } from "convex/values";

export default defineSchema({
  users: defineTable({
    name: v.string(),
    role: v.string(),
    email: v.string(),
    avatar: v.optional(v.string()),
    createdAt: v.number(),
  }).index("role", ["role"]),

  students: defineTable({
    name: v.string(),
    roll: v.string(),
    class: v.string(),
    section: v.string(),
    house: v.string(),
    gender: v.string(),
    father: v.string(),
    mother: v.string(),
    mobile: v.string(),
    email: v.optional(v.string()),
    address: v.string(),
    gc: v.string(),
    attendance: v.optional(v.number()),
    avgMarks: v.optional(v.number()),
    createdAt: v.number(),
  }).index("class", ["class"]),

  staff: defineTable({
    name: v.string(),
    role: v.string(),
    email: v.string(),
    phone: v.string(),
    designation: v.optional(v.string()),
    department: v.optional(v.string()),
    createdAt: v.number(),
  }),

  notices: defineTable({
    title: v.string(),
    body: v.string(),
    type: v.string(),
    postedBy: v.string(),
    postedAt: v.number(),
    audience: v.string(),
  }).index("audience", ["audience"]),

  fees: defineTable({
    studentId: v.string(),
    studentName: v.string(),
    amount: v.number(),
    due: v.boolean(),
    paidAt: v.optional(v.number()),
    paidBy: v.optional(v.string()),
    year: v.string(),
  }).index("studentId", ["studentId"]),

  attendance: defineTable({
    studentId: v.string(),
    date: v.string(),
    status: v.string(),
    reason: v.optional(v.string()),
  }).index("date", ["date"]),

  exams: defineTable({
    title: v.string(),
    subject: v.string(),
    date: v.string(),
    class: v.string(),
    totalMarks: v.number(),
    score: v.optional(v.number()),
    grade: v.optional(v.string()),
  }).index("class", ["class"]),

  timetable: defineTable({
    class: v.string(),
    subject: v.string(),
    teacher: v.string(),
    day: v.string(),
    period: v.number(),
  }).index("day", ["day"]),

  messages: defineTable({
    sender: v.string(),
    recipient: v.string(),
    subject: v.string(),
    body: v.string(),
    read: v.boolean(),
    createdAt: v.number(),
  }).index("recipient", ["recipient"]),

  settings: defineTable({
    key: v.string(),
    value: v.string(),
  }).index("key", ["key"]),
});
