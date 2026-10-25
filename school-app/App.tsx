import { StyleSheet, Text, View, ScrollView, TouchableOpacity, Alert } from "react-native";
import { useFonts } from "expo-font";
import { NavigationContainer } from "@react-navigation/native";
import { createBottomTabNavigator } from "@react-navigation/bottom-tabs";
import { Ionicons } from "@expo/vector-icons";

// Public website - DAV Public School Dugda
const PublicScreen = () => (
  <View style={styles.container}>
    <Text style={styles.title}>DAV Public School Dugda</Text>
    <Text style={styles.subtitle}>School Management System</Text>
    <Text style={styles.body}>
      Welcome to the public website. Maintained by the admin team.
      Login to access your panel.
    </Text>
    <TouchableOpacity style={styles.button} onPress={() => Alert.alert("Coming soon", "Login to your panel.")}>
      <Text style={styles.buttonText}>Sign In</Text>
    </TouchableOpacity>
  </View>
);

// Student portal
const StudentScreen = () => (
  <View style={styles.container}>
    <Text style={styles.title}>Student Portal</Text>
    <Text style={styles.subtitle}>Your classes, attendance, grades & timetable</Text>
    <Text style={styles.body}>Student dashboard is under construction.</Text>
    <TouchableOpacity style={styles.button} onPress={() => Alert.alert("Coming soon", "Student dashboard.")}>
      <Text style={styles.buttonText}>Go to Dashboard</Text>
    </TouchableOpacity>
  </View>
);

// Parent portal
const ParentScreen = () => (
  <View style={styles.container}>
    <Text style={styles.title}>Parent Portal</Text>
    <Text style={styles.subtitle}>Track your child's attendance, grades & fees</Text>
    <Text style={styles.body}>Parent dashboard is under construction.</Text>
    <TouchableOpacity style={styles.button} onPress={() => Alert.alert("Coming soon", "Parent dashboard.")}>
      <Text style={styles.buttonText}>Go to Dashboard</Text>
    </TouchableOpacity>
  </View>
);

// Teacher portal
const TeacherScreen = () => (
  <View style={styles.container}>
    <Text style={styles.title}>Teacher Portal</Text>
    <Text style={styles.subtitle}>Mark attendance, upload grades & notices</Text>
    <Text style={styles.body}>Teacher dashboard is under construction.</Text>
    <TouchableOpacity style={styles.button} onPress={() => Alert.alert("Coming soon", "Teacher dashboard.")}>
      <Text style={styles.buttonText}>Go to Dashboard</Text>
    </TouchableOpacity>
  </View>
);

// Admin portal
const AdminScreen = () => (
  <View style={styles.container}>
    <Text style={styles.title}>Admin Panel</Text>
    <Text style={styles.subtitle}>Complete school management & website editor</Text>
    <Text style={styles.body}>Admin: admin@davdugda.org / admin@123</Text>
    <TouchableOpacity style={styles.button} onPress={() => Alert.alert("Coming soon", "Admin tools will appear here.")}>
      <Text style={styles.buttonText}>Go to Dashboard</Text>
    </TouchableOpacity>
  </View>
);

const Tab = createBottomTabNavigator();

export default function App() {
  const [fontsLoaded] = useFonts({
    Inter: require("./assets/fonts/Inter-Regular.ttf"),
  });

  if (!fontsLoaded) {
    return <View style={styles.center}><Text>Loading &hellip;</Text></View>;
  }

  return (
    <NavigationContainer>
      <Tab.Navigator
        screenOptions={({ route }) => ({
          tabBarIcon: ({ color, size }) => {
            if (route.name === "Public") return <Ionicons name="school" size={size} color={color} />;
            if (route.name === "Student") return <Ionicons name="school-outline" size={size} color={color} />;
            if (route.name === "Parent") return <Ionicons name="people-outline" size={size} color={color} />;
            if (route.name === "Teacher") return <Ionicons name="book-outline" size={size} color={color} />;
            if (route.name === "Admin") return <Ionicons name="settings-outline" size={size} color={color} />;
            return null;
          },
          tabBarActiveTintColor: "#1a3a6b",
          tabBarActiveBackgroundColor: "#f8fafc",
          tabBarStyle: { paddingVertical: 8 },
        })}
      >
        <Tab.Screen name="Public" component={PublicScreen} />
        <Tab.Screen name="Student" component={StudentScreen} />
        <Tab.Screen name="Parent" component={ParentScreen} />
        <Tab.Screen name="Teacher" component={TeacherScreen} />
        <Tab.Screen name="Admin" component={AdminScreen} />
      </Tab.Navigator>
    </NavigationContainer>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f8fafc", padding: 20 },
  center: { flex: 1, justifyContent: "center", alignItems: "center" },
  title: { fontSize: 28, fontWeight: "800", color: "#1a3a6b", marginBottom: 8 },
  subtitle: { fontSize: 18, fontWeight: "600", color: "#7a1f2b", marginBottom: 20 },
  body: { fontSize: 15, color: "#475569", lineHeight: 22, marginBottom: 24 },
  button: { backgroundColor: "#1a3a6b", padding: 14, borderRadius: 12, alignItems: "center" },
  buttonText: { color: "#fff", fontSize: 16, fontWeight: "700" },
});
