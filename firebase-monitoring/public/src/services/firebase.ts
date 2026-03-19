/**
 * Firebase Configuration and Initialization
 * Project: webai-audit
 */

import { initializeApp, getApps, FirebaseApp } from 'firebase/app';
import { getFunctions, connectFunctionsEmulator, Functions } from 'firebase/functions';
import { getFirestore, Firestore } from 'firebase/firestore';
import { getAuth, Auth } from 'firebase/auth';
import { getAnalytics, Analytics } from 'firebase/analytics';

// Your web app's Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyDKwujMamQ_UCCqTg0F26--Mp7uszFXHPc",
  authDomain: "webai-audit.firebaseapp.com",
  projectId: "webai-audit",
  storageBucket: "webai-audit.firebasestorage.app",
  messagingSenderId: "631556970889",
  appId: "1:631556970889:web:0114170d0227c6de35e958",
  measurementId: "G-7JR11T9NS0"
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

// Initialize Cloud Functions (asia-south1 region)
export const functions: Functions = getFunctions(app, 'asia-south1');

// Initialize Analytics (only in browser)
export let analytics: Analytics | null = null;
if (typeof window !== 'undefined') {
  try {
    analytics = getAnalytics(app);
  } catch (error) {
    console.log('Analytics initialization skipped');
  }
}

// Connect to emulator in development
if (process.env.NODE_ENV === 'development') {
  try {
    connectFunctionsEmulator(functions, 'localhost', 5001);
    console.log('Connected to Functions emulator on localhost:5001');
  } catch (error) {
    console.log('Emulator already connected or not available');
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
