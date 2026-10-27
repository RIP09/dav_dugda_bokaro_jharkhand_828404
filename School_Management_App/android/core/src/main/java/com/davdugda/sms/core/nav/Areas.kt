package com.davdugda.sms.core.nav

import com.davdugda.sms.modules.dashboard.*
import com.davdugda.sms.modules.auth.*
import com.davdugda.sms.modules.profile.*
import com.davdugda.sms.modules.students.*
import com.davdugda.sms.modules.parents.*
import com.davdugda.sms.modules.teachers.*
import com.davdugda.sms.modules.staff.*
import com.davdugda.sms.modules.academics.*
import com.davdugda.sms.modules.attendance.*
import com.davdugda.sms.modules.timetable.*
import com.davdugda.sms.modules.homework.*
import com.davdugda.sms.modules.exams.*
import com.davdugda.sms.modules.fees.*
import com.davdugda.sms.modules.library.*
import com.davdugda.sms.modules.transport.*
import com.davdugda.sms.modules.hostel.*
import com.davdugda.sms.modules.admissions.*
import com.davdugda.sms.modules.certificates.*
import com.davdugda.sms.modules.accounts.*
import com.davdugda.sms.modules.payroll.*
import com.davdugda.sms.modules.inventory.*
import com.davdugda.sms.modules.front_office.*
import com.davdugda.sms.modules.communication.*
import com.davdugda.sms.modules.reports.*
import com.davdugda.sms.modules.settings.*
import com.davdugda.sms.modules.search.*
import com.davdugda.sms.modules.generic.*

/** Every management area (one folder each under modules/). */
val AllAreas: List<ModuleArea> by lazy { listOf(DashboardArea, AdmissionsArea, StudentsArea, ParentsArea, TeachersArea, StaffArea, AcademicsArea, TimetableArea, HomeworkArea, AttendanceArea, ExamsArea, CertificatesArea, FeesArea, AccountsArea, PayrollArea, InventoryArea, HostelArea, TransportArea, LibraryArea, FrontOfficeArea, CommunicationArea, ReportsArea, SettingsArea, SearchArea, ProfileArea, AuthArea) }

fun areaOfModule(key: String): ModuleArea? = AllAreas.firstOrNull { key in it.keys }
fun areaOfScreen(screen: String): ModuleArea? = AllAreas.firstOrNull { screen in it.screens }
