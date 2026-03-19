/**
 * Firebase Configuration and Initialization
 * Update these values with your Firebase project config
 */

import { initializeApp, getApps, FirebaseApp } from 'firebase/app';
import { getFunctions, connectFunctionsEmulator, Functions } from 'firebase/functions';
import { getFirestore, Firestore } from 'firebase/firestore';
import { getAuth, Auth } from 'firebase/auth';

// Your Firebase project configuration
// Get these from Firebase Console → Project Settings → General
const firebaseConfig = {
  apiKey: process.env.REACT_APP_FIREBASE_API_KEY || "YOUR_API_KEY",
  authDomain: process.env.REACT_APP_FIREBASE_AUTH_DOMAIN || "your-project.firebaseapp.com",
  projectId: process.env.REACT_APP_FIREBASE_PROJECT_ID || "your-project-id",
  storageBucket: process.env.REACT_APP_FIREBASE_STORAGE_BUCKET || "your-project.appspot.com",
  messagingSenderId: process.env.REACT_APP_FIREBASE_MESSAGING_SENDER_ID || "123456789",
  appId: process.env.REACT_APP_FIREBASE_APP_ID || "1:123456789:web:abcdef",
  measurementId: process.env.REACT_APP_FIREBASE_MEASUREMENT_ID || "G-XXXXXXXXXX"
};

// Initialize Firebase
let app: FirebaseApp;
if (!getApps().length) {
  app = initializeApp(firebaseConfig);
} else {
  app = getApps()[0];
}

// Initialize Services
export const auth: Auth = getAuth(app);
export const db: Firestore = getFirestore(app);

// Initialize Cloud Functions
export const functions: Functions = getFunctions(app, 'asia-south1'); // Change region as needed

// Connect to emulator in development
if (process.env.NODE_ENV === 'development') {
  try {
    connectFunctionsEmulator(functions, 'localhost', 5001);
    console.log('Connected to Functions emulator');
  } catch (error) {
    console.log('Emulator already connected');
  }
}

export default app;

// Helper function to call Firebase Cloud Functions
export async function callFunction<T = any>(functionName: string, data?: any): Promise<T> {
  const { httpsCallable } = await import('firebase/functions');
  const callable = httpsCallable(functions, functionName);
  const result = await callable(data);
  return result.data as T;
}
