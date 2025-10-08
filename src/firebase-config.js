
import { initializeApp } from "firebase/app";
import { getAuth } from "firebase/auth";
import { getFirestore } from "firebase/firestore";

const firebaseConfig = {
  apiKey: "AIzaSyDGim4IkNRi9DKlr5KwmcRmagJUXLmVzfc",
  authDomain: "sreemeditec-final.firebaseapp.com",
  projectId: "sreemeditec-final",
  storageBucket: "sreemeditec-final.firebasestorage.app",
  messagingSenderId: "236444837209",
  appId: "1:236444837209:web:16d3497b8b8c5566eb9848",
  measurementId: "G-M9RDRTWRR6"
};

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const db = getFirestore(app);

export { auth, db };
